<% if $HasSlides %>
  <% require css('antlion/swiper-slider:client/css/swiperhero.css') %>
<div class="swiper" id="$ClassName.Lowercase-slider-$ID">
  <div class="swiper-wrapper">
    <% if $SlidesActive.Exists %>
    <% loop $SlidesActive %>
      <div class="swiper-slide">
        <% if $Image %>
            <% if $Lazy %>
                <!-- LAZY -->
                <picture>
                <source media="(min-width: 1024px)" data-srcset="$Image.FocusFill(2000,800).URL">
                <source media="(min-width: 640px)"  data-srcset="$Image.FocusFill(1400,700).URL">

                <% if $MobileImage %>
                    <source media="(max-width: 639px)" data-srcset="$MobileImage.FocusFill(960,1024).URL">
                <% else %>
                    <source media="(max-width: 639px)" data-srcset="$Image.FocusFill(960,1024).URL">
                <% end_if %>
                <img
                    class="swiper-lazy swiper-h-{$Up.Height}"
                    data-src="$Image.FocusFill(1400,700).URL"
                    alt="$Image.Title.ATT"
                    width="1400" height="700"
                    style="width:100%;height:100%;object-fit:cover;object-position:center;">
                </picture>
                <div class="swiper-lazy-preloader"></div>
            <% else %>
                <!-- EAGER -->
                <picture>
                <source media="(min-width: 1024px)" srcset="$Image.FocusFill(2000,800).URL">
                <source media="(min-width: 640px)"  srcset="$Image.FocusFill(1400,700).URL">

                <% if $MobileImage %>
                    <source media="(max-width: 639px)" srcset="$MobileImage.FocusFill(960,1024).URL">
                <% else %>
                    <source media="(max-width: 639px)" srcset="$Image.FocusFill(960,1024).URL">
                <% end_if %>
                <img
                    class="swiper-h-{$Up.Height}"
                    src="$Image.FocusFill(1400,700).URL"
                    alt="$Image.Title.ATT"
                    width="1400" height="700"
                    style="width:100%;height:100%;object-fit:cover;object-position:center;">
                </picture>
            <% end_if %>
        <% end_if %>

        <% if $OverlayOpacity %>
            <div class="swiper-overlay" style="--overlay: {$OverlayOpacityCss};"></div>
        <% else %>
            <div class="swiper-overlay"></div>
        <% end_if %>
        <div class="slide-content">
        <div class="grid-container" style="width: 100%;">
            <div class="grid-x swiper-{$Theme} align-middle <% if $Align == 'center' %>align-center<% else_if $Align == 'right' %>align-right<% else %>align-left<% end_if %>">
                <div class="cell large-shrink small-12">
                    <% if $Headline %><h2>$Headline</h2><% end_if %>
                    <% if $Description %><p>$Description</p><% end_if %>
                    $Content
                    <% if $Links.Exists %>
                        <div class="button-group large <% if $Align == 'center' %>align-center<% else_if $Align == 'right' %>align-right<% else %>align-left<% end_if %>">
                        <% loop $Links %>
                            <a class="button $CssClass" href="$URL" <% if $OpenInNew %>target="_blank" rel="noopener noreferrer"<% end_if %>>$Title.XML</a>
                        <% end_loop %>
                        </div>
                    <% end_if %>
                </div>
            </div>
        </div>
        </div>
      </div>
    <% end_loop %>
    <% end_if %>
  </div>

  <% if $Pagination %><div class="swiper-pagination"></div><% end_if %>
  <% if $Navigation %>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
  <% end_if %>
  <% if $Scrollbar %><div class="swiper-scrollbar"></div><% end_if %>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('{$ClassName.Lowercase}-slider-{$ID}');
    if (!el) return;

    var options = {$SwiperOptionsJSON.RAW};
    new Swiper(el, options);
    });
</script>
<% end_if %>
