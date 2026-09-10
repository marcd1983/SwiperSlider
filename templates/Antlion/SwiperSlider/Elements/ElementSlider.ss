<% if $HasSlides %>
  <% require css('antlion/swiper-slider:client/css/swiperhero.css') %>
<% cached $SlidesCacheKey %>
<div class="hero swiper"
    id="slider-$ID"
    data-swiper='{$getSwiperOptionsJSON.RAW}'
>
  <div class="swiper-wrapper">
    <% if $SlidesActive.Exists %>
    <% loop $SlidesActive %>
      <% include SwiperSlide %>
    <% end_loop %>
    <% end_if %>
  </div>

  <% if $Pagination %><div class="swiper-pagination"></div><% end_if %>
  <% if $Navigation %>
    <div class="swiper-button-container">
      <div class="swiper-button-prev"></div>
      <div class="swiper-button-next"></div>
    </div>
  <% end_if %>
  <% if $Autoplay && $AutoplayProgress %>
        <div class="autoplay-progress">
            <svg viewBox="0 0 48 48">
              <circle cx="24" cy="24" r="20"></circle>
            </svg>
            <span></span>
        </div>
    <% end_if %>
  <% if $Scrollbar %><div class="swiper-scrollbar"></div><% end_if %>
</div>
<% end_cached %>
<% end_if %>
