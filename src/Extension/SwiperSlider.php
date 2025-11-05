<?php
namespace Antlion\SwiperSlider\Extension;

use Antlion\SwiperSlider\Model\SlideImage;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextCheckboxGroupField;
use SilverStripe\Forms\Field;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class SwiperSlider extends Extension
{
    private static $db = [
        // Core
        // 'Height'          => 'Enum("auto,short,medium,tall,full","tall")',
        'Effect'          => "Enum('slide,fade,coverflow,flip,cube,creative,cards','slide')",
        'Loop'            => 'Boolean',
        'Speed'           => 'Int',           // transition speed (ms)
        // 'Direction'       => 'Enum("horizontal,vertical","horizontal")',
        // Feature toggles
        'Pagination'      => 'Boolean',
        'Navigation'      => 'Boolean',
        'Scrollbar'       => 'Boolean',
        'Autoplay'        => 'Boolean',
        'AutoplayDelay'   => 'Int',           // ms (only used if Autoplay = true)
        'Lazy'            => 'Boolean',
        // 'Zoom'            => 'Boolean',
        // 'Parallax'        => 'Boolean',
    ];

    private static $has_many = [
        'Slides' => SlideImage::class,
    ];

    private static $owns = [
        'Slides',
    ];

    public function populateDefaults(): void
    {
        // DO NOT call parent::populateDefaults() in an Extension
        $this->owner->Speed         = 600;
        $this->owner->Pagination    = true;
        $this->owner->Navigation    = true;
        $this->owner->Loop          = true;
        $this->owner->Autoplay      = true;
        $this->owner->AutoplayDelay = 5000;
    }

    public function updateCMSFields(FieldList $fields): void
    {
        // Tab scaffold
        if (!$fields->fieldByName('Root.HeroSlider')) {
            $fields->addFieldToTab('Root', Tab::create('HeroSlider'));
        }

        // Slides grid (orderable)
        $gridConfig = GridFieldConfig_RelationEditor::create();
        $gridConfig->addComponent(new GridFieldOrderableRows('SortOrder'));
        $slidesGrid = GridField::create(
            'Slides',
            'Slides',
            $this->owner->Slides(),
            $gridConfig
        );
        $fields->addFieldToTab('Root.HeroSlider', $slidesGrid);

        // Settings
        $fields->addFieldToTab('Root.HeroSlider',
            ToggleCompositeField::create(
                'SliderSettings',
                'Slider Settings',
                [
                    // DropdownField::create('Height', 'Height', [
                    //     'auto'   => 'Auto',
                    //     'short'  => 'Short',
                    //     'medium' => 'Medium',
                    //     'tall'   => 'Tall',
                    //     'full'   => 'Full viewport',
                    // ]),
                    DropdownField::create('Effect', 'Effect', [
                        'slide'     => 'Slide',
                        'fade'      => 'Fade',
                        'coverflow' => 'Coverflow',
                        'flip'      => 'Flip',
                        'cube'      => 'Cube',
                        'creative'  => 'Creative',
                        'cards'     => 'Cards',
                    ]),
                    // DropdownField::create('Direction', 'Direction', [
                    //     'horizontal' => 'Horizontal',
                    //     'vertical'   => 'Vertical',
                    // ]),
                    CheckboxField::create('Loop', 'Loop'),
                    CheckboxField::create('Pagination', 'Pagination'),
                    CheckboxField::create('Navigation', 'Navigation (prev/next arrows)'),
                    CheckboxField::create('Scrollbar', 'Scrollbar'),
                    CheckboxField::create('Lazy', 'Lazy images'),
                    // CheckboxField::create('Zoom', 'Zoom'),
                    // CheckboxField::create('Parallax', 'Parallax'),
                    CheckboxField::create('Autoplay', 'Autoplay'),
                    NumericField::create('AutoplayDelay', 'Autoplay delay (ms)')
                        ->setDescription('Used only when Autoplay is enabled.'),
                    NumericField::create('Speed', 'Transition speed (ms)'),
                ]
            )->setStartClosed(false)
        );
    }

    /**
     * Build a Swiper options array from the DB config.
     */
    public function getSwiperOptions(): array
    {
        $o = [
            'effect'          => $this->owner->Effect ?: 'slide',
            // 'direction'       => $this->owner->Direction ?: 'horizontal',
            'loop'            => (bool) $this->owner->Loop,
            'speed'           => (int)  ($this->owner->Speed ?: 600),
        ];
        if ($this->owner->Pagination) {
            $o['pagination'] = [
                'el'        => '.swiper-pagination',
                'clickable' => true,
            ];
        }
        if ($this->owner->Navigation) {
            $o['navigation'] = [
                'nextEl' => '.swiper-button-next',
                'prevEl' => '.swiper-button-prev',
            ];
        }
        if ($this->owner->Scrollbar) {
            $o['scrollbar'] = [
                'el'   => '.swiper-scrollbar',
                'hide' => false,
            ];
        }
        if ($this->owner->Autoplay) {
            $o['autoplay'] = [
                'delay'               => (int)($this->owner->AutoplayDelay ?: 5000),
                'disableOnInteraction'=> false,
                'pauseOnMouseEnter'   => true,
            ];
        }
        if ($this->owner->Lazy) {
            $o['lazy'] = [
                'loadPrevNext' => true,
            ];
        }
        // if ($this->owner->Zoom) {
        //     $o['zoom'] = [
        //         'maxRatio' => 2,
        //         'minRatio' => 1,
        //     ];
        // }
        // if ($this->owner->Parallax) {
        //     $o['parallax'] = true;
        // }

        return $o;
    }

    /**
     * JSON for template injection.
     */
    public function getSwiperOptionsJSON(): string
    {
        return json_encode($this->getSwiperOptions(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Convenience: Does this object actually have slides?
     */
    public function getHasSlides(): bool
    {
        /** @var DataList $slides */
        $slides = $this->owner->Slides();
        return $slides && $slides->exists();
    }
    public function getSlidesActive(): \SilverStripe\ORM\DataList
    {
        /** @var \SilverStripe\ORM\DataList $list */
        $list = $this->owner->Slides();

        // In case the relation isn’t set on this owner yet
        if (!$list) {
            return SlideImage::get()->where('1 = 0'); // empty list
        }

        return $list->where(SlideImage::activeFilterSQL());
    }
    // public function onAfterInit(): void
    // {
    //     // If you prefer “only when used”, you can check $this->owner->dataRecord->getHasSlides()
    //     // when the dataRecord has SwiperSliderExtension applied.
    //     Requirements::css('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
    //     Requirements::javascript('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js');
    // }
}
