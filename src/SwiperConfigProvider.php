<?php

namespace Antlion\SwiperSlider;

use Antlion\SwiperSlider\Model\SlideImage;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Shared Swiper front-end helpers for anything that owns a `Slides` has_many of
 * {@see SlideImage} records and carries the Effect/Loop/... settings columns —
 * currently the page extension ({@see \Antlion\SwiperSlider\Extension\SwiperSlider})
 * and the Elemental block ({@see \Antlion\SwiperSlider\Elements\ElementSlider}).
 *
 * Implementers provide {@see swiperConfigRecord()} returning the DataObject that
 * actually holds the settings + relation ($this->owner for the extension, $this
 * for the element).
 */
trait SwiperConfigProvider
{
    /**
     * The record carrying the slider settings columns and the Slides() relation.
     *
     * @return \SilverStripe\ORM\DataObject
     */
    abstract protected function swiperConfigRecord();

    public function getSwiperOptions(): array
    {
        $r = $this->swiperConfigRecord();

        $o = [
            'effect' => $r->Effect ?: 'slide',
            'loop'   => (bool) $r->Loop,
            'speed'  => (int) ($r->Speed ?: 600),
        ];

        if ($r->Pagination) {
            $o['pagination'] = ['el' => '.swiper-pagination', 'clickable' => true];
        }
        if ($r->Navigation) {
            $o['navigation'] = ['nextEl' => '.swiper-button-next', 'prevEl' => '.swiper-button-prev'];
        }
        if ($r->Scrollbar) {
            $o['scrollbar'] = ['el' => '.swiper-scrollbar', 'hide' => false];
        }
        if ($r->Autoplay) {
            $o['autoplay'] = [
                'delay'                => (int) ($r->AutoplayDelay ?: 5000),
                'disableOnInteraction' => false,
                'pauseOnMouseEnter'    => true,
            ];
        }
        if ($r->Lazy) {
            $o['preloadImages'] = false;
            $o['lazy'] = ['loadPrevNext' => true, 'loadOnTransitionStart' => true];
        }

        return $o;
    }

    public function getSwiperOptionsJSON(): string
    {
        return json_encode($this->getSwiperOptions(), JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function getHasSlides(): bool
    {
        $slides = $this->swiperConfigRecord()->Slides();
        return $slides && $slides->exists();
    }

    public function getSlidesActive(): DataList
    {
        $slides = $this->swiperConfigRecord()->Slides();
        if (!$slides) {
            return SlideImage::get()->where('1 = 0');
        }
        return $slides->where(SlideImage::activeFilterSQL());
    }

    /**
     * Cache key for the front-end render: changes whenever the record's own
     * settings change, the slide list is added to/removed/reordered/edited,
     * a slide's linkfield buttons change, or the calendar day rolls over
     * (slides can be scheduled by date, so a key that never changes would
     * freeze which slides are "active").
     */
    public function getSlidesCacheKey(): string
    {
        $r      = $this->swiperConfigRecord();
        $slides = $r->Slides();

        // linkfield writes Link records directly without touching the owning
        // SlideImage's LastEdited, so fold the slide buttons' own timestamp in.
        $linksLastEdited = ($slides && $slides->exists())
            ? $slides->relation('Links')->max('LastEdited')
            : '';

        return md5(implode('|', [
            $r->ID,
            $r->LastEdited,
            $slides ? $slides->count() : 0,
            $slides ? implode('-', $slides->sort('SortOrder')->column('ID')) : '',
            $slides ? $slides->max('LastEdited') : '',
            $linksLastEdited,
            DBDatetime::now()->Format('yyyy-MM-dd'),
        ]));
    }
}
