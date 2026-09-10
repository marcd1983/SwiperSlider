<?php

namespace Antlion\SwiperSlider\Elements;

use DNADesign\Elemental\Controllers\ElementController;
use SilverStripe\View\Requirements;

class ElementSliderController extends ElementController
{
    protected function init(): void
    {
        parent::init();

        /** @var ElementSlider $element */
        $element = $this->getElement();

        if (!$element->getHasSlides()) {
            return;
        }

        Requirements::javascript('antlion/swiper-slider:client/js/swiper-bundle.min.js');

        $id      = (int) $element->ID;
        $options = $element->getSwiperOptionsJSON();

        $js = <<<JS
        (function(){
          function initSlider_{$id}(){
            var el = document.getElementById('slider-{$id}');
            if (!el || el.__swiperInit) return;
            el.__swiperInit = true;
            var options = {$options};

            var progressWrap = el.querySelector('.autoplay-progress');
            if (options.autoplay && progressWrap) {
              var progressCircle  = progressWrap.querySelector('svg');
              var progressContent = progressWrap.querySelector('span');

              options.on = options.on || {};
              options.on.autoplayTimeLeft = function(swiper, time, progress) {
                if (progressCircle)  progressCircle.style.setProperty('--progress', 1 - progress);
                if (progressContent) progressContent.textContent = Math.ceil(time / 1000) + 's';
              };
            }

            if (window.Swiper) new Swiper(el, options);
          }

          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSlider_{$id}, { once: true });
          } else {
            initSlider_{$id}();
          }
        })();
        JS;

        Requirements::customScript($js, "swiper-element-init-{$id}");
    }
}
