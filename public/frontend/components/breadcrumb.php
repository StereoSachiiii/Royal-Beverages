
<div class="breadcrumb-container" >

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    let urlString = window.location.href;
    
    let path = urlString.replace(/^(https?:\/\/[^\/]+)/, ''); 
    
    const array = path.split('/').splice(3);
    
    
    const pathSegments = array.filter(segment => segment.trim() !== '');

    const breadcrumbContainer = document.querySelector('.breadcrumb-container');

    let html = '';

    const textMap = {
        'index': '<a href = "<?=BASE_URL?>/index.php"> Home</a>',
        'cart': '<a href = "<?=BASE_URL?>/cart.php"> Cart</a>',
        'categories': '<a href = "<?=BASE_URL?>/category.php ">Categories</a>',
        'profile': '<a href = "<?=BASE_URL?>/myaccount/profile.php">Profile</a>',
        'feedback': '<a href = " <?=BASE_URL?>/cart.php">Feedback</a>',
        'past_orders': '<a href = "<?=BASE_URL?>/cart.php ">Past Orders</a>',
        'featured': '<a href = "<?=BASE_URL?>/cart.php ">Featured</a>',
        'about_us': '<a href = "<?=BASE_URL?>/cart.php ">About Us</a>'
    };
    
    pathSegments.map((page, index) => {
        let cleanPageName = page.replace(/\.(php|html|htm)$/i, '');

        let displayText = textMap[cleanPageName.toLowerCase()] || cleanPageName.charAt(0).toUpperCase() + cleanPageName.slice(1);
        
        html += `<div class="breadcrumb-item">${displayText}</div>`;
    });


    if (html === '' && pathSegments.length === 0) {
        breadcrumbContainer.innerHTML = '<div class="breadcrumb-item">Home</div>';
    } else {
        breadcrumbContainer.innerHTML = html;
    }
});
    </script>
