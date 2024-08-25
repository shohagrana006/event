$(document).ready(function(){
  $('ul li a').click(function(){
    $('li a').removeClass("active");
    $(this).addClass("active");
});
});


  $(".toggle-password").click(function() {
    $(this).toggleClass("fa-eye fa-eye-slash");
    input = $(this).parent().find("input");
    if (input.attr("type") == "password") {
        input.attr("type", "text");
    } else {
        input.attr("type", "password");
    }
});


$(document).ready(function(){
  $(".box").on('click',function(){
    $(".box").toggleClass("main");
  });

});

$(function(){
  $('.selectpicker').selectpicker();
});


$("button.funcuones-wrapper").click(function () {
  $(".rotate").toggleClass("down");
})


$("button.funcuones-wrapper").click(function () {
  $(".main-comprar-table").toggleClass("show");
})
