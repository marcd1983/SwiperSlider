<?php
namespace Antlion\SwiperSlider\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\File;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\DateField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationResult;

class SlideImage extends DataObject
{
    private static $table_name = 'SlideImage';
    private static $singular_name = 'Slide';
    private static $plural_name   = 'Slides';

    private static $db = [
        'Name'           => 'Varchar(255)',
        'Content'        => 'HTMLText',
        'Theme'          => 'Enum("light,dark","dark")',
        'Align'          => 'Enum("center,left,right","left")',
        'OverlayOpacity' => 'Int',
        'StartDate'      => 'Date',
        'EndDate'        => 'Date',
        'SortOrder'      => 'Int',
        'MediaType'      => 'Enum("image,video","image")',
        'VideoStart'     => 'Int',   // seconds
        'VideoEnd'       => 'Int',   // seconds (0 = full)
        'HideContentContainer' => 'Boolean',
    ];

    private static $has_one = [
        'Image'       => Image::class,
        'MobileImage' => Image::class,
        'VideoMP4'    => File::class,
        'VideoWebM'   => File::class,
        'VideoPoster' => Image::class,
        'Parent'      => SiteTree::class,
        'CoverLink'   => Link::class,
    ];

    private static $has_many = [
        'Links' => Link::class . '.Owner',
    ];

    private static $owns = [
        'Image',
        'MobileImage',
        'VideoMP4',
        'VideoWebM',
        'VideoPoster',
        'Links',
        'CoverLink',
    ];

    private static $default_sort = 'SortOrder';

    private static $summary_fields = [
        'CMSThumb'           => 'Preview',
        'Name'               => 'Name',
        'MediaType'          => 'Type',
        'StartDate'          => 'Starts',
        'EndDate'            => 'Ends',
    ];

    public function getCMSThumb()
    {
        if ($this->MediaType === 'video') {
            return $this->VideoPoster()->exists()
                ? $this->VideoPoster()->CMSThumbnail()
                : '(video)';
        }
        return $this->Image()->exists() ? $this->Image()->CMSThumbnail() : '';
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
            'EndDate',
            'CoverLinkID',
            'VideoStart',
            'VideoEnd',
        ]);

        // Media toggle (before image)
        $fields->insertBefore(
            'Image',
            DropdownField::create('MediaType', 'Media type (Choose Image or Video)', [
                'image' => 'Image',
                'video' => 'Video (HTML5)',
            ])
        );

        // Images
        $fields->replaceField(
            'Image',
            UploadField::create('Image', 'Desktop image')
                ->setAllowedFileCategories('image/supported')
                ->setFolderName('swiper/slides')
                ->setDescription('Optimal 2000×800')
                ->displayIf('MediaType')->isEqualTo('image')->end()
        );
        $fields->replaceField(
            'MobileImage',
            UploadField::create('MobileImage', 'Mobile image')
                ->setAllowedFileCategories('image/supported')
                ->setFolderName('swiper/slides') // 🔧 keep folder consistent
                ->setDescription('Optional; fallback is desktop image. Optimal 960×1024')
                ->displayIf('MediaType')->isEqualTo('image')->end()
        );

        // Videos
        $mp4    = UploadField::create('VideoMP4', 'Video (MP4)');
        $webm   = UploadField::create('VideoWebM', 'Video (WebM, optional)');
        $poster = UploadField::create('VideoPoster', 'Poster image (optional)');

        $mp4->getValidator()->setAllowedExtensions(['mp4']);
        $webm->getValidator()->setAllowedExtensions(['webm']);

        $mp4->displayIf('MediaType')->isEqualTo('video')->end();
        $webm->displayIf('MediaType')->isEqualTo('video')->end();
        $poster->displayIf('MediaType')->isEqualTo('video')->end();

        $start = NumericField::create('VideoStart', 'Video Start (seconds)')
            ->displayIf('MediaType')->isEqualTo('video')->end();
        $end = NumericField::create('VideoEnd', 'Video End (seconds, 0 = full)')
            ->displayIf('MediaType')->isEqualTo('video')->end();

        $fields->addFieldsToTab('Root.Main', [$mp4, $webm, $poster, $start, $end]);

        // Appearance
        $fields->addFieldToTab(
            'Root.Main',
            FieldGroup::create(
                'Appearance',
                DropdownField::create('Theme', 'Theme', [
                    'light' => 'Light',
                    'dark'  => 'Dark',
                ]),
                DropdownField::create('Align', 'Content alignment', [
                    'left'   => 'Left',
                    'right'  => 'Right',
                    'center' => 'Center',
                ]),
                NumericField::create('OverlayOpacity', 'Overlay opacity (0–100)')
                    ->setDescription('Typical: 0–70')
            )->setName('AppearanceGroup')->addExtraClass('stack')
        );

        // Schedule
        $fields->addFieldToTab(
            'Root.Main',
            FieldGroup::create(
                'Schedule',
                DateField::create('StartDate', 'Start date')
                    ->setHTML5(true)
                    ->setDescription('Optional. Slide visible on/after this date.'),
                DateField::create('EndDate', 'End date')
                    ->setHTML5(true)
                    ->setDescription('Optional. Slide remains visible through this date.')
            )->setName('ScheduleGroup')->addExtraClass('stack')
        );

        // Buttons (many)
        $buttonLinkField = MultiLinkField::create('Links', 'Buttons')
            ->setDescription('Button links placed in the slide');

        // Cover link (single)
        $coverLinkField = LinkField::create('CoverLink', 'Cover link')
            ->setDescription('Optional: a link that wraps the entire slide');

        // Mutually exclusive hints
        $hasCoverLink = (bool)$this->CoverLinkID;
        $hasButtons   = (bool)$this->Links()->exists();
        if ($hasCoverLink) {
            $buttonLinkField->setDisabled(true)
                ->setDescription('Disabled because a Cover Link is set. Remove it to enable Buttons.');
        } elseif ($hasButtons) {
            $coverLinkField->setDisabled(true)
                ->setDescription('Disabled because Buttons exist. Remove all buttons to enable Cover Link.');
        }

        $fields->addFieldToTab('Root.Main', $buttonLinkField);
        $fields->addFieldToTab('Root.Main', $coverLinkField);

        // Simple conditional help (no hard hide/show in core)
        if ($this->MediaType === 'image') {
            $mp4->setDescription('Switch Media type to “Video (HTML5)” to use these.');
            $webm->setDescription('Switch Media type to “Video (HTML5)” to use these.');
        }

        return $fields;
    }

    public function OverlayOpacityCss(): string
    {
        $pct = max(0, min(100, (int)$this->OverlayOpacity));
        return (string) round($pct / 100, 2);
    }

    public function IsActive(): bool
    {
        $today = DBDatetime::now()->DateString(); // YYYY-MM-DD
        $start = $this->StartDate ?: null;
        $end   = $this->EndDate ?: null;

        if (!$start && !$end) return true;
        if ($start && !$end)  return $start <= $today;
        if (!$start && $end)  return $today <= $end;
        return $start <= $today && $today <= $end;
    }

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
        $this->OverlayOpacity = max(0, min(100, (int)$this->OverlayOpacity));
    }

    public function validate(): ValidationResult
    {
        $result = parent::validate();

        // Cover vs Buttons
        if ($this->CoverLinkID && $this->Links()->exists()) {
            $result->addError('Choose either a Cover Link or Buttons, not both.');
        }

        // Minimal media requirements
        if ($this->MediaType === 'image' && !$this->ImageID) {
            $result->addError('Please upload a Desktop image (or switch Media type to Video).');
        }
        if ($this->MediaType === 'video' && !$this->VideoMP4ID && !$this->VideoWebMID) {
            $result->addError('Please upload at least an MP4 or WebM for the video slide.');
        }

        // Clip range sanity
        if ($this->VideoStart && $this->VideoEnd && $this->VideoEnd < $this->VideoStart) {
            $result->addError('Video End must be greater than or equal to Start.');
        }

        return $result;
    }

    // Helpers used by the template
    public function getIsVideo(): bool
    {
        return $this->MediaType === 'video';
    }

    public function getPosterURL(): ?string
    {
        return $this->VideoPoster()->exists()
            ? $this->VideoPoster()->Fill(2000, 800)->getURL()
            : null;
    }
}
