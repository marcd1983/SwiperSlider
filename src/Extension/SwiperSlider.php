<?php
namespace Antlion\SwiperSlider\Extension;

use Antlion\SwiperSlider\Model\SlideImage;
use Antlion\SwiperSlider\SwiperConfigProvider;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class SwiperSlider extends Extension
{
    use SwiperConfigProvider;

    private static $db = [
        'Margin'  => "Enum('none,small,medium,large','none')",
        'Effect'        => "Enum('slide,fade,coverflow,flip,cube,creative,cards','slide')",
        'Loop'          => 'Boolean',
        'Speed'         => 'Int',
        'Pagination'    => 'Boolean',
        'Navigation'    => 'Boolean',
        'Scrollbar'     => 'Boolean',
        'Autoplay'      => 'Boolean',
        'AutoplayDelay' => 'Int',
        'Lazy'          => 'Boolean',
        'AutoplayProgress' => 'Boolean',
        'DesktopWidth'  => 'Int',
        'DesktopHeight' => 'Int',
        'MobileWidth'   => 'Int',
        'MobileHeight'  => 'Int',
    ];

    private static $has_many = ['Slides' => SlideImage::class];
    private static $owns     = ['Slides'];

    protected function swiperConfigRecord()
    {
        return $this->owner;
    }

    public function populateDefaults(): void
    {
        $this->owner->Speed = 600;
        $this->owner->Pagination = true;
        $this->owner->Navigation = true;
        $this->owner->Loop = true;
        $this->owner->Autoplay = true;
        $this->owner->AutoplayDelay = 5000;
        $this->owner->AutoplayProgress = true;
        $this->owner->DesktopWidth = 1920;
        $this->owner->DesktopHeight = 700;
        $this->owner->MobileWidth = 960;
        $this->owner->MobileHeight = 1024;
    }

    public function updateCMSFields(FieldList $fields): void
    {
        if (!$fields->fieldByName('Root.HeroSlider')) {
            $fields->addFieldToTab('Root', Tab::create('HeroSlider'));
        }

        // Drop the scaffolded settings fields + the auto Slides tab; we place
        // our own versions inside Root.HeroSlider below.
        $fields->removeByName([
            'Margin',
            'Effect',
            'Loop',
            'Speed',
            'Pagination',
            'Navigation',
            'Scrollbar',
            'Lazy',
            'Autoplay',
            'AutoplayDelay',
            'AutoplayProgress',
            'Slides',
            'DesktopWidth',
            'DesktopHeight',
            'MobileWidth',
            'MobileHeight',
        ]);

        // Slides grid (orderable)
        $gridConfig = GridFieldConfig_RelationEditor::create();
        $gridConfig->addComponent(new GridFieldOrderableRows('SortOrder'));
        $fields->addFieldToTab('Root.HeroSlider', GridField::create(
            'Slides',
            'Slides',
            $this->owner->Slides(),
            $gridConfig
        ));

        // Settings
        $fields->addFieldToTab('Root.HeroSlider',
            ToggleCompositeField::create('SliderSettings', 'Slider Settings', [
                DropdownField::create('Margin',  'Bottom Margin',  $this->spacingOptions()),
                DropdownField::create('Effect', 'Effect', [
                    'slide'=>'Slide','fade'=>'Fade','coverflow'=>'Coverflow','flip'=>'Flip',
                    'cube'=>'Cube','creative'=>'Creative','cards'=>'Cards',
                ]),
                CheckboxField::create('Loop', 'Loop'),
                CheckboxField::create('Pagination', 'Pagination'),
                CheckboxField::create('Navigation', 'Navigation (prev/next)'),
                CheckboxField::create('Scrollbar', 'Scrollbar'),
                CheckboxField::create('Lazy', 'Lazy images'),
                CheckboxField::create('Autoplay', 'Autoplay'),
                CheckboxField::create('AutoplayProgress', 'Show autoplay progress'),
                NumericField::create('AutoplayDelay', 'Autoplay delay (ms)'),
                NumericField::create('Speed', 'Transition speed (ms)'),
                FieldGroup::create('Desktop dimensions',
                    NumericField::create('DesktopWidth', 'Width (px)'),
                    NumericField::create('DesktopHeight', 'Height (px)')
                )->setName('DesktopDimensions'),
                FieldGroup::create('Mobile dimensions',
                    NumericField::create('MobileWidth', 'Width (px)'),
                    NumericField::create('MobileHeight', 'Height (px)')
                )->setName('MobileDimensions'),
            ])->setStartClosed(false)
        );
    }
    private function spacingOptions(): array
    {
        return [
            'none'   => 'None',
            'small'  => 'Small',
            'medium' => 'Medium',
            'large'  => 'Large',
        ];
    }

    public function MarginClasses(): string
    {
        return match ($this->owner->Margin) {
            'small' => 'mb-1', 'medium' => 'mb-2', 'large' => 'mb-3', default => ''
        };
    }
}
