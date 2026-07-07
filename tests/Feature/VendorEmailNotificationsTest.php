<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class VendorEmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Model helpers
    // -------------------------------------------------------------------------

    private function makeVendorUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'username'          => fake()->unique()->userName(),
            'role'              => 'vendor',
            'email_verified_at' => now(),
            'is_active'         => true,
        ], $overrides));
    }

    private function makeAdminUser(): User
    {
        return User::factory()->create([
            'username' => fake()->unique()->userName(),
            'role'     => 'admin',
        ]);
    }

    private function makeVendor(User $vendorUser): Vendor
    {
        return Vendor::create([
            'user_id'       => $vendorUser->id,
            'name'          => 'Test Vendor Shop',
            'contact_email' => $vendorUser->email,
            'contact_phone' => '08000000000',
            'address'       => '1 Test Street, Lagos',
            'is_active'     => true,
        ]);
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'name' => 'Sports ' . fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
        ]);
    }

    private function makeProduct(Vendor $vendor, Category $category, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'name'        => 'Test Product',
            'description' => 'A test product description.',
            'price'       => 5000.00,
            'quantity'    => 50,
            'status'      => 'draft',
            'is_active'   => true,
        ], $overrides));
    }

    private function makeOrder(User $customer): Order
    {
        return Order::create([
            'user_id'         => $customer->id,
            'order_date'      => now(),
            'status'          => 'processing',
            'total_amount'    => 7000.00,
            'shipping_cost'   => 2000.00,
            'tax_amount'      => 0.00,
            'discount_amount' => 0.00,
        ]);
    }

    private function makeOrderItem(Order $order, Product $product, Vendor $vendor, int $quantity = 1): OrderItem
    {
        return OrderItem::create([
            'order_id'          => $order->id,
            'product_id'        => $product->id,
            'vendor_id'         => $vendor->id,
            'quantity'          => $quantity,
            'price_at_purchase' => $product->price,
            'subtotal'          => $product->price * $quantity,
        ]);
    }

    private function makePayment(Order $order, string $reference = 'TEST-REF-001'): Payment
    {
        return Payment::create([
            'order_id'        => $order->id,
            'amount'          => $order->total_amount,
            'payment_method'  => 'card',
            'status'          => 'pending',
            'transaction_id'  => $reference,
            'idempotency_key' => 'test-idempotency-' . $order->id,
        ]);
    }

    private function fakePaystackSuccess(string $reference): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status'  => true,
                'message' => 'Verification successful',
                'data'    => [
                    'status'    => 'success',
                    'reference' => $reference,
                    'amount'    => 700000,
                ],
            ], 200),
        ]);
    }

    // -------------------------------------------------------------------------
    // Assertion helpers
    // -------------------------------------------------------------------------

    /** Returns true if the MessageSent event has an email to the given address. */
    private function sentTo(MessageSent $event, string $email): bool
    {
        $recipients = array_map(
            fn(Address $a) => $a->getAddress(),
            $event->message->getTo()
        );
        return in_array($email, $recipients);
    }

    /** Returns true if the MessageSent event's subject contains the given string. */
    private function subjectContains(MessageSent $event, string $needle): bool
    {
        return str_contains($event->message->getSubject() ?? '', $needle);
    }

    // =========================================================================
    // Step 1 — Registration: sends VendorWelcome
    // =========================================================================

    public function test_registration_sends_vendor_welcome_email(): void
    {
        Event::fake([MessageSent::class]);

        $this->postJson('/api/v1/register', [
            'name'                  => 'Jane Vendor',
            'username'              => 'janevendor',
            'email'                 => 'jane@vendor.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => 'vendor',
            'business_name'         => 'Jane Sports',
            'business_address'      => '10 Lagos Road, Abuja',
            'business_phone'        => '08011223344',
        ])->assertStatus(201);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'jane@vendor.com')
                && $this->subjectContains($event, 'Welcome to');
        });
    }

    // =========================================================================
    // Step 2 — Product approved: sends ProductApproved
    // =========================================================================

    public function test_approving_product_sends_product_approved_email(): void
    {
        Event::fake([MessageSent::class]);

        $admin      = $this->makeAdminUser();
        $vendorUser = $this->makeVendorUser(['email' => 'vendor@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        $product    = $this->makeProduct($vendor, $category);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/products/{$product->id}/status", ['status' => 'active'])
            ->assertStatus(200);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'vendor@shop.com')
                && $this->subjectContains($event, 'now live on');
        });
    }

    // =========================================================================
    // Step 3 — Product feedback: sends ProductFeedback
    // =========================================================================

    public function test_rejecting_product_sends_product_feedback_email(): void
    {
        Event::fake([MessageSent::class]);

        $admin      = $this->makeAdminUser();
        $vendorUser = $this->makeVendorUser(['email' => 'vendor2@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        $product    = $this->makeProduct($vendor, $category);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/products/{$product->id}/status", [
                'status'   => 'inactive',
                'feedback' => 'Please add clearer product images and improve the description.',
            ])->assertStatus(200);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'vendor2@shop.com')
                && $this->subjectContains($event, 'Action required');
        });
    }

    public function test_rejecting_product_without_feedback_still_sends_product_feedback_email(): void
    {
        Event::fake([MessageSent::class]);

        $admin      = $this->makeAdminUser();
        $vendorUser = $this->makeVendorUser(['email' => 'vendor3@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        $product    = $this->makeProduct($vendor, $category);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/products/{$product->id}/status", ['status' => 'inactive'])
            ->assertStatus(200);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'vendor3@shop.com')
                && $this->subjectContains($event, 'Action required');
        });
    }

    // =========================================================================
    // Step 4 — Payment verified: sends VendorNewOrder to each unique vendor
    // =========================================================================

    public function test_payment_verification_sends_vendor_new_order_email(): void
    {
        Event::fake([MessageSent::class]);

        $customer   = User::factory()->create(['username' => 'customer1', 'role' => 'customer']);
        $vendorUser = $this->makeVendorUser(['email' => 'vendor4@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        $product    = $this->makeProduct($vendor, $category, ['quantity' => 50]);

        $order     = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product, $vendor, 1);

        $reference = 'PAY-TEST-001';
        $this->makePayment($order, $reference);
        $this->fakePaystackSuccess($reference);

        $this->actingAs($customer)
            ->postJson('/api/v1/checkout/verify', ['reference' => $reference])
            ->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'success');

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'vendor4@shop.com')
                && $this->subjectContains($event, 'New order received');
        });
    }

    public function test_payment_verification_sends_new_order_only_once_per_vendor_with_multiple_items(): void
    {
        Event::fake([MessageSent::class]);

        $customer   = User::factory()->create(['username' => 'customer2', 'role' => 'customer']);
        $vendorUser = $this->makeVendorUser(['email' => 'vendor5@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();

        $product1 = $this->makeProduct($vendor, $category, ['quantity' => 50, 'slug' => 'prod-a']);
        $product2 = $this->makeProduct($vendor, $category, ['quantity' => 50, 'slug' => 'prod-b']);

        $order = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product1, $vendor, 1);
        $this->makeOrderItem($order, $product2, $vendor, 1);

        $reference = 'PAY-TEST-002';
        $this->makePayment($order, $reference);
        $this->fakePaystackSuccess($reference);

        $this->actingAs($customer)
            ->postJson('/api/v1/checkout/verify', ['reference' => $reference])
            ->assertStatus(200);

        // Only 1 VendorNewOrder email despite 2 items from the same vendor
        $newOrderEmails = Event::dispatched(MessageSent::class, function (MessageSent $event) {
            return $this->subjectContains($event, 'New order received');
        });
        $this->assertCount(1, $newOrderEmails);
    }

    // =========================================================================
    // Step 5 — Order completed: sends VendorOrderCompleted
    // =========================================================================

    public function test_marking_order_completed_sends_vendor_order_completed_email(): void
    {
        Event::fake([MessageSent::class]);

        $admin      = $this->makeAdminUser();
        $vendorUser = $this->makeVendorUser(['email' => 'vendor6@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        $product    = $this->makeProduct($vendor, $category);

        $customer = User::factory()->create(['username' => 'customer3', 'role' => 'customer']);
        $order    = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product, $vendor);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertStatus(200);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'vendor6@shop.com')
                && $this->subjectContains($event, 'payment settlement');
        });
    }

    // =========================================================================
    // Step 6 — Order delivered: sends VendorOrderDelivered
    // =========================================================================

    public function test_marking_order_delivered_sends_vendor_order_delivered_email(): void
    {
        Event::fake([MessageSent::class]);

        $admin      = $this->makeAdminUser();
        $vendorUser = $this->makeVendorUser(['email' => 'vendor7@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        $product    = $this->makeProduct($vendor, $category);

        $customer = User::factory()->create(['username' => 'customer4', 'role' => 'customer']);
        $order    = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product, $vendor);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertStatus(200);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'vendor7@shop.com')
                && $this->subjectContains($event, 'Order successfully delivered');
        });
    }

    public function test_delivered_and_completed_send_correct_respective_emails(): void
    {
        Event::fake([MessageSent::class]);

        $admin    = $this->makeAdminUser();
        $customer = User::factory()->create(['username' => 'customer5', 'role' => 'customer']);

        $vendorUser1 = $this->makeVendorUser(['email' => 'vendorA@shop.com']);
        $vendor1     = $this->makeVendor($vendorUser1);
        $vendorUser2 = $this->makeVendorUser(['email' => 'vendorB@shop.com']);
        $vendor2     = $this->makeVendor($vendorUser2);

        $category = $this->makeCategory();
        $product1 = $this->makeProduct($vendor1, $category, ['slug' => 'prod-c']);
        $product2 = $this->makeProduct($vendor2, $category, ['slug' => 'prod-d']);

        $order1 = $this->makeOrder($customer);
        $this->makeOrderItem($order1, $product1, $vendor1);

        $order2 = $this->makeOrder($customer);
        $this->makeOrderItem($order2, $product2, $vendor2);

        $this->actingAs($admin)->patchJson("/api/v1/admin/orders/{$order1->id}/status", ['status' => 'delivered']);
        $this->actingAs($admin)->patchJson("/api/v1/admin/orders/{$order2->id}/status", ['status' => 'completed']);

        Event::assertDispatched(MessageSent::class, fn($e) =>
            $this->sentTo($e, 'vendorA@shop.com') && $this->subjectContains($e, 'Order successfully delivered')
        );
        Event::assertDispatched(MessageSent::class, fn($e) =>
            $this->sentTo($e, 'vendorB@shop.com') && $this->subjectContains($e, 'payment settlement')
        );
        Event::assertNotDispatched(MessageSent::class, fn($e) =>
            $this->sentTo($e, 'vendorA@shop.com') && $this->subjectContains($e, 'payment settlement')
        );
        Event::assertNotDispatched(MessageSent::class, fn($e) =>
            $this->sentTo($e, 'vendorB@shop.com') && $this->subjectContains($e, 'Order successfully delivered')
        );
    }

    // =========================================================================
    // Step 7 — Low stock alert: sends VendorLowStock when quantity ≤ 10
    // =========================================================================

    public function test_payment_verification_sends_low_stock_alert_when_quantity_drops_to_threshold(): void
    {
        Event::fake([MessageSent::class]);

        $customer   = User::factory()->create(['username' => 'customer6', 'role' => 'customer']);
        $vendorUser = $this->makeVendorUser(['email' => 'vendor8@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        // 11 units — buying 1 drops to 10, which is ≤10 and >0 → low stock alert fires
        $product    = $this->makeProduct($vendor, $category, ['quantity' => 11]);

        $order     = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product, $vendor, 1);

        $reference = 'PAY-LOW-001';
        $this->makePayment($order, $reference);
        $this->fakePaystackSuccess($reference);

        $this->actingAs($customer)
            ->postJson('/api/v1/checkout/verify', ['reference' => $reference])
            ->assertStatus(200);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->sentTo($event, 'vendor8@shop.com')
                && $this->subjectContains($event, 'Low stock alert');
        });
    }

    public function test_no_low_stock_alert_when_quantity_stays_above_threshold(): void
    {
        Event::fake([MessageSent::class]);

        $customer   = User::factory()->create(['username' => 'customer7', 'role' => 'customer']);
        $vendorUser = $this->makeVendorUser(['email' => 'vendor9@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        // 50 units — buying 1 drops to 49, which is >10 → no alert
        $product    = $this->makeProduct($vendor, $category, ['quantity' => 50]);

        $order     = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product, $vendor, 1);

        $reference = 'PAY-HIGH-001';
        $this->makePayment($order, $reference);
        $this->fakePaystackSuccess($reference);

        $this->actingAs($customer)
            ->postJson('/api/v1/checkout/verify', ['reference' => $reference])
            ->assertStatus(200);

        Event::assertNotDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->subjectContains($event, 'Low stock alert');
        });
    }

    public function test_no_low_stock_alert_when_product_reaches_zero(): void
    {
        Event::fake([MessageSent::class]);

        $customer   = User::factory()->create(['username' => 'customer8', 'role' => 'customer']);
        $vendorUser = $this->makeVendorUser(['email' => 'vendor10@shop.com']);
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        // Buying the last unit → quantity hits 0 → out of stock, no low stock alert (quantity > 0 check)
        $product    = $this->makeProduct($vendor, $category, ['quantity' => 1]);

        $order     = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product, $vendor, 1);

        $reference = 'PAY-ZERO-001';
        $this->makePayment($order, $reference);
        $this->fakePaystackSuccess($reference);

        $this->actingAs($customer)
            ->postJson('/api/v1/checkout/verify', ['reference' => $reference])
            ->assertStatus(200);

        Event::assertNotDispatched(MessageSent::class, function (MessageSent $event) {
            return $this->subjectContains($event, 'Low stock alert');
        });
    }

    // =========================================================================
    // Idempotency — re-verifying an already-processed payment sends no emails
    // =========================================================================

    public function test_already_processed_payment_does_not_resend_emails(): void
    {
        Event::fake([MessageSent::class]);

        $customer   = User::factory()->create(['username' => 'customer9', 'role' => 'customer']);
        $vendorUser = $this->makeVendorUser();
        $vendor     = $this->makeVendor($vendorUser);
        $category   = $this->makeCategory();
        $product    = $this->makeProduct($vendor, $category, ['quantity' => 50]);

        $order   = $this->makeOrder($customer);
        $this->makeOrderItem($order, $product, $vendor, 1);

        $reference = 'PAY-DUP-001';
        Payment::create([
            'order_id'        => $order->id,
            'amount'          => $order->total_amount,
            'payment_method'  => 'card',
            'status'          => 'success',
            'transaction_id'  => $reference,
            'idempotency_key' => 'test-dup-key',
            'processed_at'    => now(),
        ]);

        $this->actingAs($customer)
            ->postJson('/api/v1/checkout/verify', ['reference' => $reference])
            ->assertStatus(200)
            ->assertJsonPath('data.already_processed', true);

        Event::assertNotDispatched(MessageSent::class);
    }
}
