<?php

namespace Antlion\SwiperSlider\Elements;

use Antlion\SwiperSlider\Model\SlideImage;
use Antlion\SwiperSlider\SwiperConfigProvider;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class ElementSlider extends BaseElement
{
    use SwiperConfigProvider;

    private static $table_name = 'ElementSlider';
    private static $description = 'Swiper Slider';
    private static $singular_name = 'Slider';
    private static $plural_name = 'Sliders';
    private static $icon = 'font-icon-block-carousel';

    private static $controller_class = ElementSliderController::class;

    private static $inline_editable = false;

    private static $db = [
        'Effect'           => "Enum('slide,fade,coverflow,flip,cube,creative,cards','slide')",
        'Loop'             => 'Boolean',
        'Speed'            => 'Int',
        'Pagination'       => 'Boolean',
        'Navigation'       => 'Boolean',
        'Scrollbar'        => 'Boolean',
        'Autoplay'         => 'Boolean',
        'AutoplayDelay'    => 'Int',
        'Lazy'             => 'Boolean',
        'AutoplayProgress' => 'Boolean',
        'DesktopWidth'     => 'Int',
        'DesktopHeight'    => 'Int',
        'MobileWidth'      => 'Int',
        'MobileHeight'     => 'Int',
    ];

    private static $has_many = [
        'Slides' => SlideImage::class,
    ];

    private static $owns = [
        'Slides',
    ];

    protected function swiperConfigRecord()
    {
        return $this;
    }

    public function populateDefaults(): void
    {
        parent::populateDefaults();
        $this->Speed            = 600;
        $this->Pagination       = true;
        $this->Navigation       = true;
        $this->Loop             = true;
        $this->Autoplay         = true;
        $this->AutoplayDelay    = 5000;
        $this->AutoplayProgress = true;
        $this->DesktopWidth     = 1920;
        $this->DesktopHeight    = 700;
        $this->MobileWidth      = 960;
        $this->MobileHeight     = 1024;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Effect',
            'Loop',
            'Speed',
            'Pagination',
            'Navigation',
            'Scrollbar',
            'Autoplay',
            'AutoplayDelay',
            'Lazy',
            'AutoplayProgress',
            'Slides',
            'DesktopWidth',
            'DesktopHeight',
            'MobileWidth',
            'MobileHeight',
        ]);

        $gridConfig = GridFieldConfig_RelationEditor::create();
        $gridConfig->addComponent(new GridFieldOrderableRows('SortOrder'));
        $fields->addFieldToTab('Root.Main', GridField::create(
            'Slides',
            'Slides',
            $this->Slides(),
            $gridConfig
        ));

        $fields->addFieldToTab('Root.Main',
            ToggleCompositeField::create(
                'SliderSettings',
                'Slider Settings',
                [
                    DropdownField::create('Effect', 'Transition effect', [
                        'slide'     => 'Slide',
                        'fade'      => 'Fade',
                        'coverflow' => 'Coverflow',
                        'flip'      => 'Flip',
                        'cube'      => 'Cube',
                        'creative'  => 'Creative',
                        'cards'     => 'Cards',
                    ]),

                    CheckboxField::create('Loop',             'Loop'),
                    CheckboxField::create('Pagination',       'Pagination'),
                    CheckboxField::create('Navigation',       'Navigation (prev/next arrows)'),
                    CheckboxField::create('Scrollbar',        'Scrollbar'),
                    CheckboxField::create('Lazy',             'Lazy load images'),
                    CheckboxField::create('Autoplay',         'Autoplay'),
                    CheckboxField::create('AutoplayProgress', 'Show autoplay progress indicator'),
                    NumericField::create('AutoplayDelay', 'Autoplay delay (ms)')
                        ->setDescription('Used only when Autoplay is enabled.'),
                    NumericField::create('Speed', 'Transition speed (ms)'),
                    FieldGroup::create('Desktop dimensions',
                        NumericField::create('DesktopWidth', 'Width (px)'),
                        NumericField::create('DesktopHeight', 'Height (px)')
                    )->setName('DesktopDimensions'),
                    FieldGroup::create('Mobile dimensions',
                        NumericField::create('MobileWidth', 'Width (px)'),
                        NumericField::create('MobileHeight', 'Height (px)')
                    )->setName('MobileDimensions'),
                ]
            )->setStartClosed(false)
        );

        return $fields;
    }

    public function getType(): string
    {
        return _t(__CLASS__ . '.BlockType', 'Slider');
    }
}
