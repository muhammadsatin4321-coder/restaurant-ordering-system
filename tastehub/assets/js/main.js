document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 1. PRELOADER HANDLER
    // ==========================================
    const hidePreloader = () => {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.style.opacity = '0';
            preloader.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 300);
        }
    };

    hidePreloader();
    setTimeout(hidePreloader, 3000); // Fallback timeout

    // ==========================================
    // 2. THIRD-PARTY LIBRARIES INIT
    // ==========================================
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 800, once: true });
    }

    // Swiper Hero
    if (typeof Swiper !== 'undefined' && document.querySelector('.hero-swiper')) {
        new Swiper('.hero-swiper', {
            loop: true,
            autoplay: { delay: 4000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
        });
    }

    // Swiper Testimonials
    if (typeof Swiper !== 'undefined' && document.querySelector('.testimonial-swiper')) {
        new Swiper('.testimonial-swiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            breakpoints: {
                768: { slidesPerView: 2 },
                1024: { slidesPerView: 3 }
            },
            pagination: { el: '.swiper-pagination', clickable: true }
        });
    }

    // ==========================================
    // 3. UI CONTROLS (Dark Mode & Scroll)
    // ==========================================
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="bi bi-sun-fill text-warning"></i>';
        }

        darkModeToggle.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            darkModeToggle.innerHTML = isDark 
                ? '<i class="bi bi-sun-fill text-warning"></i>' 
                : '<i class="bi bi-moon-fill"></i>';
        });
    }

    // Scroll to Top Button
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            scrollTopBtn.style.display = window.scrollY > 200 ? "flex" : "none";
        });

        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});

// ==========================================
// 4. AJAX ACTIONS (Cart, Wishlist, Coupon)
// ==========================================
async function postAJAX(url, bodyParams) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bodyParams
        });

        const isJson = response.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await response.json() : null;

        if (response.status === 401 || (data && data.status === 'unauthorized')) {
            showLoginAlert();
            return null;
        }

        if (!response.ok || !data) {
            throw new Error(data?.message || 'Invalid server response');
        }

        return data;
    } catch (error) {
        console.error(`Request Error [${url}]:`, error);
        showLoginAlert();
        return null;
    }
}

async function addToCart(foodId) {
    const data = await postAJAX('cart_action.php', `action=add&food_id=${encodeURIComponent(foodId)}&quantity=1`);
    if (!data) return;

    if (data.status === 'success') {
        Swal.fire({ 
            icon: 'success', 
            title: 'Added to Cart!', 
            text: data.message || 'Item added successfully.', 
            timer: 1500, 
            showConfirmButton: false 
        });
        const cartBadge = document.getElementById('cartCount');
        if (cartBadge && data.cartCount !== undefined) {
            cartBadge.innerText = data.cartCount;
        }
    } else {
        Swal.fire({ icon: 'warning', title: 'Notice', text: data.message });
    }
}

async function toggleWishlist(foodId) {
    const data = await postAJAX('wishlist_action.php', `food_id=${encodeURIComponent(foodId)}`);
    if (!data) return;

    if (data.status === 'success') {
        Swal.fire({ 
            icon: 'success', 
            title: 'Wishlist Updated', 
            text: data.message, 
            timer: 1500, 
            showConfirmButton: false 
        });
        const wishBadge = document.getElementById('wishlistCount');
        if (wishBadge && data.wishlistCount !== undefined) {
            wishBadge.innerText = data.wishlistCount;
        }
    } else {
        Swal.fire({ icon: 'info', title: 'Notice', text: data.message });
    }
}

function showLoginAlert() {
    Swal.fire({
        icon: 'error',
        title: 'Authentication Required',
        text: 'Please login to perform this action.',
        showCancelButton: true,
        confirmButtonColor: '#FF385C',
        confirmButtonText: 'Login Now',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'login.php';
        }
    });
}

function copyCoupon(code) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => {
            Swal.fire({ 
                icon: 'success', 
                title: 'Copied!', 
                text: 'Coupon code ' + code + ' copied to clipboard.', 
                timer: 1500, 
                showConfirmButton: false 
            });
        }).catch(err => console.error('Copy failed:', err));
    }
}