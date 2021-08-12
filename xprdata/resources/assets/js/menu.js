$('.money-menus').removeClass('active');

const params = new URLSearchParams(window.location.search);

const p = params.get("p");

if(!!p) {
    $('.twelve-menutabs li.money-menus a[href*="' + p + '"]').closest("li").addClass("active");
} else {
    $('.default-page-commission').addClass('active');
}