<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class ShipmentController extends Controller
{
    /**
     * Create a new shipment and assign order items.
     * Expects: order_item_ids[] (array), driver_id (optional)
     */
    public function createShipment(Request $request): JsonResponse
    {
        $request->validate([
            'order_item_ids' => 'required|array|min:1',
            'order_item_ids.*' => 'exists:order_items,id',
            'driver_id' => 'nullable|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $shipment = Shipment::create([
                'shipment_id' => 'SHIP-' . strtoupper(Str::random(10)),
                'driver_id' => $request->driver_id,
                'status' => 'pending',
            ]);

            // Assign order items and generate tracking_id for each
            foreach ($request->order_item_ids as $orderItemId) {
                $orderItem = OrderItem::find($orderItemId);
                $orderItem->shipment_id = $shipment->id;
                $orderItem->tracking_id = 'TRK-' . strtoupper(Str::random(12));
                $orderItem->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shipment created and order items assigned',
                'data' => $shipment->fresh('orderItems'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update shipment location/status (driver/admin).
     * Expects: shipment_id, location, status
     */
    public function updateShipment(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|exists:shipments,shipment_id',
            'location' => 'required|string',
            'status' => 'required|string',
        ]);

        $shipment = Shipment::where('shipment_id', $request->shipment_id)->first();
        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found',
            ], 404);
        }

        // Update current location and status
        $shipment->current_location = $request->location;
        $shipment->status = $request->status;
        $shipment->save();

        // Log update
        ShipmentUpdate::create([
            'shipment_id' => $shipment->id,
            'location' => $request->location,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated',
            'data' => $shipment->fresh('updates'),
        ]);
    }

    /**
     * Customer tracking endpoint: Get shipment status by tracking_id.
     */
    public function trackByTrackingId($tracking_id): JsonResponse
    {
        $orderItem = OrderItem::where('tracking_id', $tracking_id)->with(['shipment.updates'])->first();
        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Tracking ID not found',
            ], 404);
        }

        $shipment = $orderItem->shipment;
        return response()->json([
            'success' => true,
            'message' => 'Tracking info retrieved',
            'data' => [
                'order_item' => $orderItem,
                'shipment' => $shipment,
                'updates' => $shipment ? $shipment->updates : [],
            ],
        ]);
    }
}
