// Frontend Integration Examples

// ============================================
// 1. CART MANAGEMENT
// ============================================

// Add item to cart
async function addToCart(productId, quantity) {
  try {
    const response = await fetch('http://localhost:8000/api/v1/cart/add', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        product_id: productId,
        quantity: quantity
      })
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Item added to cart:', data.data);
      updateCartBadge(data.data.total_items);
      showNotification('Added to cart successfully!');
    } else {
      showError(data.message);
    }
  } catch (error) {
    showError('Failed to add item to cart');
  }
}

// Get cart
async function getCart() {
  try {
    const response = await fetch('http://localhost:8000/api/v1/cart', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    
    if (data.success) {
      return data.data;
    }
  } catch (error) {
    console.error('Failed to fetch cart:', error);
  }
}

// Update cart item quantity
async function updateCartItem(itemId, quantity) {
  try {
    const response = await fetch(`http://localhost:8000/api/v1/cart/items/${itemId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ quantity })
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Cart updated:', data.data);
      return data.data;
    } else {
      showError(data.message);
    }
  } catch (error) {
    showError('Failed to update cart');
  }
}

// Remove item from cart
async function removeCartItem(itemId) {
  try {
    const response = await fetch(`http://localhost:8000/api/v1/cart/items/${itemId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Item removed from cart');
      return data.data;
    }
  } catch (error) {
    showError('Failed to remove item');
  }
}

// Clear entire cart
async function clearCart() {
  try {
    const response = await fetch('http://localhost:8000/api/v1/cart/clear', {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Cart cleared');
      return data.data;
    }
  } catch (error) {
    showError('Failed to clear cart');
  }
}

// ============================================
// 2. CHECKOUT & PAYMENT
// ============================================

// Get checkout summary
async function getCheckoutSummary() {
  try {
    const response = await fetch('http://localhost:8000/api/v1/checkout/summary', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    
    if (data.success) {
      return data.data;
      // Returns: { subtotal, shipping_cost, tax_amount, total_amount, items_count }
    }
  } catch (error) {
    console.error('Failed to fetch checkout summary:', error);
  }
}

// Initialize checkout and payment
async function initializeCheckout(shippingAddressId, billingAddressId, notes = '') {
  try {
    const response = await fetch('http://localhost:8000/api/v1/checkout/initialize', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        shipping_address_id: shippingAddressId,
        billing_address_id: billingAddressId || shippingAddressId,
        notes: notes
      })
    });

    const data = await response.json();
    
    if (data.success) {
      // Redirect to Paystack payment page
      window.location.href = data.data.payment_url;
      
      // Store reference for later verification
      localStorage.setItem('payment_reference', data.data.reference);
      localStorage.setItem('order_id', data.data.order_id);
      
      return data.data;
    } else {
      showError(data.message);
    }
  } catch (error) {
    showError('Failed to initialize checkout');
  }
}

// Verify payment (call this on your callback page)
async function verifyPayment(reference) {
  try {
    const response = await fetch('http://localhost:8000/api/v1/checkout/verify', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ reference })
    });

    const data = await response.json();
    
    if (data.success && data.data.payment_status === 'success') {
      // Payment successful
      showSuccessMessage('Payment successful! Your order is being processed.');
      
      // Clean up stored data
      localStorage.removeItem('payment_reference');
      
      // Redirect to order confirmation page
      window.location.href = `/orders/${data.data.order_id}`;
    } else {
      // Payment failed
      showError('Payment failed. Please try again.');
      window.location.href = '/cart';
    }
    
    return data.data;
  } catch (error) {
    showError('Failed to verify payment');
  }
}

// ============================================
// 3. COMPLETE CHECKOUT FLOW
// ============================================

// Example: Complete checkout button handler
async function handleCheckout() {
  try {
    // 1. Show loading
    showLoading('Processing checkout...');
    
    // 2. Get selected shipping address
    const shippingAddressId = document.getElementById('shipping_address').value;
    
    if (!shippingAddressId) {
      showError('Please select a shipping address');
      return;
    }
    
    // 3. Get optional notes
    const notes = document.getElementById('order_notes').value;
    
    // 4. Initialize checkout (this will redirect to Paystack)
    await initializeCheckout(shippingAddressId, shippingAddressId, notes);
    
  } catch (error) {
    hideLoading();
    showError('Checkout failed. Please try again.');
  }
}

// Example: Payment callback handler
// This runs on your /payment/callback page
function handlePaymentCallback() {
  const urlParams = new URLSearchParams(window.location.search);
  const reference = urlParams.get('reference');
  const status = urlParams.get('status');
  
  if (reference) {
    // Show verifying message
    showLoading('Verifying payment...');
    
    // Verify with backend
    verifyPayment(reference);
  } else {
    showError('Invalid payment callback');
    window.location.href = '/cart';
  }
}

// ============================================
// 4. REACT EXAMPLE
// ============================================

// React Hook for Cart
function useCart() {
  const [cart, setCart] = useState(null);
  const [loading, setLoading] = useState(false);

  const fetchCart = async () => {
    setLoading(true);
    const data = await getCart();
    setCart(data);
    setLoading(false);
  };

  const addItem = async (productId, quantity) => {
    await addToCart(productId, quantity);
    await fetchCart(); // Refresh cart
  };

  const updateItem = async (itemId, quantity) => {
    await updateCartItem(itemId, quantity);
    await fetchCart(); // Refresh cart
  };

  const removeItem = async (itemId) => {
    await removeCartItem(itemId);
    await fetchCart(); // Refresh cart
  };

  useEffect(() => {
    fetchCart();
  }, []);

  return { cart, loading, addItem, updateItem, removeItem };
}

// React Component Example
function CartPage() {
  const { cart, loading, updateItem, removeItem } = useCart();

  if (loading) return <div>Loading...</div>;
  if (!cart || cart.items.length === 0) return <div>Cart is empty</div>;

  return (
    <div className="cart">
      <h1>Shopping Cart ({cart.total_items} items)</h1>
      
      {cart.items.map(item => (
        <div key={item.id} className="cart-item">
          <img src={item.product_image} alt={item.product_name} />
          <div>
            <h3>{item.product_name}</h3>
            <p>₦{item.price.toLocaleString()}</p>
            <input 
              type="number" 
              value={item.quantity}
              onChange={(e) => updateItem(item.id, parseInt(e.target.value))}
            />
            <button onClick={() => removeItem(item.id)}>Remove</button>
          </div>
          <div>₦{item.subtotal.toLocaleString()}</div>
        </div>
      ))}
      
      <div className="cart-total">
        <h2>Total: ₦{cart.total_amount.toLocaleString()}</h2>
        <button onClick={() => window.location.href = '/checkout'}>
          Proceed to Checkout
        </button>
      </div>
    </div>
  );
}

// ============================================
// 5. UTILITY FUNCTIONS
// ============================================

function showNotification(message) {
  // Implement your notification logic
  alert(message);
}

function showError(message) {
  // Implement your error notification logic
  alert('Error: ' + message);
}

function showSuccessMessage(message) {
  // Implement your success notification logic
  alert(message);
}

function showLoading(message) {
  // Show loading spinner
  console.log('Loading:', message);
}

function hideLoading() {
  // Hide loading spinner
  console.log('Loading complete');
}

function updateCartBadge(count) {
  // Update cart badge/icon in navbar
  const badge = document.getElementById('cart-badge');
  if (badge) {
    badge.textContent = count;
  }
}

// ============================================
// 6. ERROR HANDLING
// ============================================

// Comprehensive error handler
function handleApiError(error, response) {
  if (response.status === 401) {
    // Unauthorized - redirect to login
    localStorage.removeItem('token');
    window.location.href = '/login';
  } else if (response.status === 422) {
    // Validation error
    const errors = response.data.errors;
    Object.keys(errors).forEach(field => {
      showError(`${field}: ${errors[field].join(', ')}`);
    });
  } else if (response.status === 400) {
    // Bad request (e.g., out of stock)
    showError(response.data.message);
  } else {
    // General error
    showError('An error occurred. Please try again.');
  }
}
