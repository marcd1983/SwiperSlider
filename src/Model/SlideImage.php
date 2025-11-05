<?php
namespace Antlion\SwiperSlider\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Forms\DateField;

class SlideImage extends DataObject
{
    private static $table_name = 'SlideImage';
    private static $singular_name = 'Slide';
    private static $plural_name   = 'Slides';

    private static $db = [
        'Name'           => 'Varchar(255)',
        'Content'        => 'HTMLText',
        'Theme' => 'Enum("light,dark","dark")',
        'Align' => 'Enum("center,left,right","left")',
        'OverlayOpacity' => 'Int',
        'StartDate'       => 'Date',
        'EndDate'         => 'Date',
        'SortOrder'      => 'Int',
    ];

    private static $has_one = [
        'Image' => Image::class,
        'MobileImage' => Image::class,
        'Parent' => SiteTree::class,
        // Parent slider owner is provided by the extension’s has_many relation:
        // 'OwnerPage' => <added by relation on extended object>
    ];

    // LinkField stores polymorphic relations via Link::class . '.Owner'
    private static $has_many = [
        'Links' => Link::class . '.Owner',
    ];

    private static $owns = [
        'Image',
        'MobileImage',
        'Links',
    ];

    private static $default_sort = 'SortOrder';

    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Image',
        'Name'               => 'Name',
        'StartDate'          => 'Starts',
        'EndDate'            => 'Ends',
    ];

    public function OverlayOpacityCss(): string
    {
        $pct = max(0, min(100, (int) $this->OverlayOpacity));
        return (string) round($pct / 100, 2);
    }

    /**
     * Return true if this slide should be visible *today*.
     * Rules:
     * 1) No dates set => visible
     * 2) Only EndDate set => visible until end date passes
     * 3) Start + End set => visible if today is within [start..end]
     */
    public function IsActive(): bool
    {
        $today = DBDatetime::now()->DateString(); // 'YYYY-MM-DD'
        $start = $this->StartDate ?: null;
        $end   = $this->EndDate ?: null;

        if (!$start && !$end) {
            return true;
        }
        if ($start && !$end) {
            return $start <= $today;
        }
        if (!$start && $end) {
            return $today <= $end;
        }
        // both set
        return $start <= $today && $today <= $end;
    }

    /**
     * SQL fragment you can reuse to filter a DataList in PHP/controller code.
     */
    public static function activeFilterSQL(): string
    {
        return '("StartDate" IS NULL OR "StartDate" <= CURRENT_DATE())'
             . ' AND ("EndDate" IS NULL OR "EndDate" >= CURRENT_DATE())';
    }
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->StartDate && $this->EndDate && $this->EndDate < $this->StartDate) {
            $this->EndDate = $this->StartDate;
        }
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'Links',
            'SortOrder',
            'ParentID', 
            'Theme', 
            'Align', 
            'OverlayOpacity', 
            'StartDate', 
            'EndDate'
        ]);
        // Main
        $fields->replaceField(
            'Image',
            UploadField::create('Image', 'Desktop image')
                ->setAllowedFileCategories('image/supported')
                ->setFolderName('swiper/slides')
                ->setDescription('Optimal Size 2000px x 800px')
        );
        $fields->replaceField(
            'MobileImage',
            UploadField::create('MobileImage', 'Mobile image')
                ->setAllowedFileCategories('image/supported')
                ->setFolderName('uploads/swiper slides')
                ->setDescription('Optional. If left empty, desktop image will be used. Optimal Size 960px x 1024px')
        );

        $fields->addFieldToTab('Root.Main',
        FieldGroup::create(
            'Appearance', // group title (must be first arg)
            DropdownField::create('Theme', 'Theme', [
                'light' => 'Light',
                'dark'  => 'Dark',
            ]),
            DropdownField::create('Align', 'Content Block Alignment', [
                'left'   => 'Left',
                'right'  => 'Right',
                'center' => 'Center',
            ]),
            NumericField::create('OverlayOpacity', 'Overlay opacity (0–100)')
                ->setDescription('Typical: 0–70')
            )
            ->setName('AppearanceGroup')
            ->addExtraClass('stack'),    
           
        );
        $fields->addFieldToTab('Root.Main',
            FieldGroup::create(
                'Schedule',
                DateField::create('StartDate', 'Start date')
                    ->setHTML5(true)
                    ->setDescription('Optional. Slide becomes visible on/after this date.'),
                DateField::create('EndDate', 'End date')
                    ->setHTML5(true)
                    ->setDescription('Optional. Slide remains visible through this date.')
            )->setName('ScheduleGroup')->addExtraClass('stack')
        );
        $fields->addFieldToTab('Root.Main', 
         MultiLinkField::create('Links', 'Buttons')
        );

        return $fields;
    }
}
