<?php
namespace kilyakus\widget\grid;

use Closure;
use kilyakus\widgets\BootstrapInterface;
use kilyakus\widgets\BootstrapTrait;
use kilyakus\widgets\Config;
use kartik\dialog\Dialog;
use Yii;
use yii\base\InvalidConfigException;
use yii\grid\Column;
use yii\grid\GridView as YiiGridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\Request;
use yii\widgets\Pjax;
use kilyakus\portlet\Portlet;
use kilyakus\button\Button;
use kilyakus\switcher\SwitcherAsset;

class GridView extends YiiGridView implements BootstrapInterface
{
	use BootstrapTrait;

	const POS_TOP = 'top';
	const POS_BOTTOM = 'bottom';

	const ICON_ACTIVE = '<span class="glyphicon glyphicon-ok text-success"></span>';
	const ICON_INACTIVE = '<span class="glyphicon glyphicon-remove text-danger"></span>';
	const ICON_EXPAND = '<span class="glyphicon glyphicon-expand"></span>';
	const ICON_COLLAPSE = '<span class="glyphicon glyphicon-collapse-down"></span>';
	const ICON_ACTIVE_BS4 = '<span class="fas fa-check text-success"></span>';
	const ICON_INACTIVE_BS4 = '<span class="fas fa-times text-danger"></span>';
	const ICON_EXPAND_BS4 = '<span class="far fa-plus-square"></span>';
	const ICON_COLLAPSE_BS4 = '<span class="far fa-minus-square"></span>';

	const ROW_NONE = -1;
	const ROW_EXPANDED = 0;
	const ROW_COLLAPSED = 1;

	const ALIGN_RIGHT = 'right';
	const ALIGN_CENTER = 'center';
	const ALIGN_LEFT = 'left';
	const ALIGN_TOP = 'top';
	const ALIGN_MIDDLE = 'middle';
	const ALIGN_BOTTOM = 'bottom';

	const NOWRAP = 'kv-nowrap';

	const FILTER_CHECKBOX = 'checkbox';
	const FILTER_RADIO = 'radio';

	const FILTER_SELECT2 = '\kilyakus\select2\Select2';
    const FILTER_RANGE = '\kilyakus\range\Range';
	const FILTER_TYPEAHEAD = '\kartik\typeahead\Typeahead';
	const FILTER_SWITCH = '\kartik\switchinput\SwitchInput';
	const FILTER_SPIN = '\kartik\touchspin\TouchSpin';
	const FILTER_STAR = '\kartik\rating\StarRating';
	const FILTER_DATE = '\kartik\date\DatePicker';
	const FILTER_TIME = '\kartik\time\TimePicker';
	const FILTER_DATETIME = '\kartik\datetime\DateTimePicker';
	const FILTER_DATE_RANGE = '\kartik\daterange\DateRangePicker';
	const FILTER_SORTABLE = '\kartik\sortinput\SortableInput';
	const FILTER_COLOR = '\kartik\color\ColorInput';
	const FILTER_SLIDER = '\kartik\slider\Slider';
	const FILTER_MONEY = '\kartik\money\MaskMoney';
	const FILTER_NUMBER = '\kartik\number\NumberControl';
	const FILTER_CHECKBOX_X = '\kartik\checkbox\CheckboxX';

	const F_COUNT = 'f_count';
	const F_SUM = 'f_sum';
	const F_MAX = 'f_max';
	const F_MIN = 'f_min';
	const F_AVG = 'f_avg';

	const HTML = 'html';
	const CSV = 'csv';
	const TEXT = 'txt';
	const EXCEL = 'xls';
	const PDF = 'pdf';
	const JSON = 'json';

	const TARGET_POPUP = '_popup';
	const TARGET_SELF = '_self';
	const TARGET_BLANK = '_blank';

	public $moduleId;

	public $krajeeDialogSettings = [];

	public $layout = "{summary}\n{items}\n{pager}";

	public $itemLabelSingle;
	public $itemLabelPlural;
	public $itemLabelFew;
	public $itemLabelMany;
	public $itemLabelAccusative;

	public $panelTemplate = <<< HTML
{panelBefore}
{items}
{panelAfter}
HTML;

	public $panelBeforeTemplate = <<< HTML
	{toolbarContainer}
	{before}
	<div class="clearfix"></div>
HTML;

	public $panelAfterTemplate = '{after}';

	public $portletAppend = false;
	public $portlet = [];

	public $beforeHeader = [];
	public $afterHeader = [];
	public $beforeFooter = [];
	public $afterFooter = [];

	public $toolbar = [
		'{toggleData}',
		'{export}',
	];

	public $toolbarContainerOptions = ['class' => 'btn-toolbar kv-grid-toolbar toolbar-container'];

	public $replaceTags = [];

	public $dataColumnClass = 'kilyakus\widget\grid\DataColumn';

	public $footerRowOptions = ['class' => 'kv-table-footer'];

	public $captionOptions = ['class' => 'kv-table-caption'];

	public $tableOptions = [];

	public $pjax = false;

	public $pjaxSettings = [];

	public $resizableColumns = true;

	public $hideResizeMobile = true;

	public $resizableColumnsOptions = ['resizeFromBody' => false];

	public $persistResize = false;

	public $resizeStorageKey;

	public $bootstrap = true;

	public $bordered = true;

	public $striped = true;

	public $condensed = false;

	public $responsive = true;

	public $responsiveWrap = true;

	public $hover = false;

	public $floatHeader = false;

	public $floatOverflowContainer = false;

	public $floatHeaderOptions = ['top' => 50];

	public $perfectScrollbar = false;

	public $perfectScrollbarOptions = [];

	public $showPageSummary = false;

	public $pageSummaryPosition = self::POS_BOTTOM;

	public $pageSummaryContainer = ['class' => 'kv-page-summary-container'];

	public $pageSummaryRowOptions = [];

	public $defaultPagination = 'page';

	public $toggleData = true;

	public $toggleDataOptions = [];

	public $toggleDataContainer = [];

	public $exportContainer = [];

	public $export = [];

	public $exportConfig = [];

	public $exportConversions = [];

	public $autoXlFormat = false;

	public $containerOptions = [];

	public $hashExportConfig = true;

	protected $_gridClientFunc = '';

	protected $_module;

	protected $_toggleDataKey;

	protected $_toggleButtonId;

	protected $_toggleOptionsVar;

	protected $_toggleScript;

	protected $_isShowAll = false;

    public $filterSelector = 'select#per-page';

	protected static function parseExportConfig($exportConfig, $defaultExportConfig)
	{
		if (is_array($exportConfig) && !empty($exportConfig)) {
			foreach ($exportConfig as $format => $setting) {
				$setup = is_array($exportConfig[$format]) ? $exportConfig[$format] : [];
				$exportConfig[$format] = empty($setup) ? $defaultExportConfig[$format] :
					array_replace_recursive($defaultExportConfig[$format], $setup);
			}
			return $exportConfig;
		}
		return $defaultExportConfig;
	}

	protected static function initCss(&$options, $css)
	{
		if (!isset($options['class'])) {
			$options['class'] = $css;
		}
	}

	public function init()
	{
		$this->initModule();
		if (isset($this->_module->bsVersion)) {
			$this->bsVersion = $this->_module->bsVersion;
		}
		$this->initBsVersion();
		Html::addCssClass($this->options, 'is-bs' . ($this->isBs4() ? '4' : '3'));
		$this->initPjaxContainerId();
		if (!isset($this->itemLabelSingle)) {
			$this->itemLabelSingle = Yii::t('kvgrid', 'item');
		}
		if (!isset($this->itemLabelPlural)) {
			$this->itemLabelPlural = Yii::t('kvgrid', 'items');
		}
		if (!isset($this->itemLabelFew)) {
			$this->itemLabelFew = Yii::t('kvgrid', 'items-few');
		}
		if (!isset($this->itemLabelMany)) {
			$this->itemLabelMany = Yii::t('kvgrid', 'items-many');
		}
		if (!isset($this->itemLabelAccusative)) {
			$this->itemLabelAccusative = Yii::t('kvgrid', 'items-acc');
		}
		$isBs4 = $this->isBs4();
		if ($isBs4) {
			Html::addCssClass($this->options, 'kv-grid-bs4');
			$this->setPagerOptionClass('linkContainerOptions', 'page-item');
			$this->setPagerOptionClass('linkOptions', 'page-link');
			$this->setPagerOptionClass('disabledListItemSubTagOptions', 'page-link');
		}
		if (!$this->toggleData) {
			parent::init();
			return;
		}
		$this->_toggleDataKey = '_tog' . hash('crc32', $this->options['id']);

		$request = $this->_module->get('request', false);
		if ($request === null || !($request instanceof Request)) {
			$request = Yii::$app->request;
		}
		$this->_isShowAll = $request->getQueryParam($this->_toggleDataKey, $this->defaultPagination) === 'all';
		if ($this->_isShowAll) {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->dataProvider->pagination = false;
		}
		$this->_toggleButtonId = $this->options['id'] . '-togdata-' . ($this->_isShowAll ? 'all' : 'page');

        $this->portletAppend = !(!$this->bootstrap || !is_array($this->portlet) || empty($this->portlet));

		parent::init();
	}

	public function getPjaxContainerId()
	{
		$this->initPjaxContainerId();
		return $this->pjaxSettings['options']['id'];
	}

	public function initPjaxContainerId()
	{
		if (empty($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
		if (empty($this->pjaxSettings['options']['id'])) {
			$this->pjaxSettings['options']['id'] = $this->options['id'] . '-pjax';
		}
	}

	protected function setPagerOptionClass($param, $css)
	{
		$opts = ArrayHelper::getValue($this->pager, $param, []);
		Html::addCssClass($opts, $css);
		$this->pager[$param] = $opts;
	}

	public function run()
	{
		$this->initToggleData();
		$this->initExport();
		if ($this->export !== false && isset($this->exportConfig[self::PDF])) {
			Config::checkDependency(
				'mpdf\Pdf',
				'yii2-mpdf',
				'for PDF export functionality. To include PDF export, follow the install steps below. If you do not ' .
				"need PDF export functionality, do not include 'PDF' as a format in the 'export' property. You can " .
				"otherwise set 'export' to 'false' to disable all export functionality"
			);
		}
		$this->initHeader();
		$this->initBootstrapStyle();
		$this->containerOptions['id'] = $this->options['id'] . '-container';
		Html::addCssClass($this->containerOptions, 'kv-grid-container');

        $this->registerAssets();

        if ($this->pjax) {

            $this->beginPjax();

	        $this->renderPortletBegin();

	        $this->initLayout();
            parent::run();

            self::renderPortletEnd();

            $this->endPjax();

        } else {

	        $this->renderPortletBegin();

	        $this->initLayout();
            parent::run();

        	self::renderPortletEnd();
        }
	}

    private function renderPortletBegin()
    {
        if($this->portletAppend !== false){

        	$this->layout = "{items}";

	        // $this->portlet['footer'] = [PageSizer::widget(), $this->renderSection('{summary}')];
	        $this->portlet['footerContent'] = Html::tag('div', parent::renderSection('{pager}'), ['class' => 'pull-left']) . Html::tag('div', PageSizer::widget() . $this->renderSection('{summary}'), ['class' => 'pull-right']);

            Portlet::begin($this->portlet);

        }else{
        	$this->layout = "{summary}<br>\n{items}" . parent::renderSection('{pager}');
        	
			return;
		}

		if ($before !== false || $before !== null) {
			static::initCss($beforeOptions, 'kv-panel-before');
			$content = strtr($this->panelBeforeTemplate, ['{before}' => $before]);
			$panelBefore = Html::tag('div', $content, $beforeOptions);
		}
		if ($after !== false && $after !== null) {
			static::initCss($afterOptions, 'kv-panel-after');
			$content = strtr($this->panelAfterTemplate, ['{after}' => $after]);
			$panelAfter = Html::tag('div', $content, $afterOptions);
		}

		$this->layout = strtr($this->panelTemplate, [
			'{panelBefore}' => $panelBefore,
			'{panelAfter}' => $panelAfter,
		]);
    }

    private function renderPortletEnd()
    {
        if($this->portletAppend){
            Portlet::end();
        }
    }

	public function renderPageSummary()
	{
		if (!$this->showPageSummary) {
			return null;
		}
		if (!isset($this->pageSummaryRowOptions['class'])) {
			$this->pageSummaryRowOptions['class'] = ($this->isBs4() ? 'table-' : '') . 'warning kv-page-summary';
		}
		Html::addCssClass($this->pageSummaryRowOptions, $this->options['id']);
		$row = $this->getPageSummaryRow();
		if ($row === null) {
			return '';
		}
		$tag = ArrayHelper::remove($this->pageSummaryContainer, 'tag', 'tbody');
		$content = Html::tag('tr', $row, $this->pageSummaryRowOptions);
		return Html::tag($tag, $content, $this->pageSummaryContainer);
	}

	protected function getPageSummaryRow()
	{
		$columns = array_values($this->columns);
		$cols = count($columns);
		if ($cols === 0) {
			return null;
		}
		$cells = [];
		$skipped = [];
		for ($i = 0; $i < $cols; $i++) {
			/** @var DataColumn $column */
			$column = $columns[$i];
			if (!method_exists($column, 'renderPageSummaryCell')) {
				$cells[] = Html::tag('td');
				continue;
			}
			$cells[] = $column->renderPageSummaryCell();
			if (!empty($column->pageSummaryOptions['colspan'])) {
				$span = (int)$column->pageSummaryOptions['colspan'];
				$dir = ArrayHelper::getValue($column->pageSummaryOptions, 'data-colspan-dir', 'ltr');
				if ($span > 0) {
					$fm = ($dir === 'ltr') ? ($i + 1) : ($i - $span + 1);
					$to = ($dir === 'ltr') ? ($i + $span - 1) : ($i - 1);
					for ($j = $fm; $j <= $to; $j++) {
						$skipped[$j] = true;
					}
				}
			}
		}
		if (!empty($skipped)) {
			for ($i = 0; $i < $cols; $i++) {
				if (isset($skipped[$i])) {
					$cells[$i] = '';
				}
			}
		}
		return implode('', $cells);
	}

	public function renderTableBody()
	{
		$content = parent::renderTableBody();
		if ($this->showPageSummary) {
			$summary = $this->renderPageSummary();
			return $this->pageSummaryPosition === self::POS_TOP ? ($summary . $content) : ($content . $summary);
		}
		return $content;
	}

	public function renderTableRow($model, $key, $index)
	{
		$cells = [];
		/* @var $column Column */
		foreach ($this->columns as $column) {
			$cells[] = $column->renderDataCell($model, $key, $index);
		}
		if ($this->rowOptions instanceof Closure) {
			$options = call_user_func($this->rowOptions, $model, $key, $index, $this);
		} else {
			$options = $this->rowOptions;
		}
		$options['data-key'] = static::parseKey($key);
		Html::addCssClass($options, $this->options['id']);
		return Html::tag('tr', implode('', $cells), $options);
	}

	public static function parseKey($key)
	{
		return is_array($key) ? Json::encode($key) : (is_object($key) ? serialize($key) : (string)$key);
	}

	public function renderToggleData()
	{
		if (!$this->toggleData) {
			return '';
		}
		$maxCount = ArrayHelper::getValue($this->toggleDataOptions, 'maxCount', false);
		if ($maxCount !== true && (!$maxCount || (int)$maxCount <= $this->dataProvider->getTotalCount())) {
			return '';
		}
		$tag = $this->_isShowAll ? 'page' : 'all';
		$options = $this->toggleDataOptions[$tag];
		$label = ArrayHelper::remove($options, 'label', '');
		$url = Url::current([$this->_toggleDataKey => $tag]);
		static::initCss($this->toggleDataContainer, 'btn-group');
		return Html::tag('div', Html::a($label, $url, $options), $this->toggleDataContainer);
	}

	public function renderExport()
	{
		if ($this->export === false || !is_array($this->export) ||
			empty($this->exportConfig) || !is_array($this->exportConfig)
		) {
			return '';
		}
		$isBs4 = $this->isBs4();
		$title = $this->export['label'];
		$icon = $this->export['icon'];
		$options = $this->export['options'];
		static::initCss($options, ['btn', $this->_defaultBtnCss]);
		$menuOptions = $this->export['menuOptions'];
		$title = ($icon == '') ? $title : "<i class='{$icon}'></i> {$title}";
		$encoding = ArrayHelper::getValue($this->export, 'encoding', 'utf-8');
		$bom = (int)ArrayHelper::getValue($this->export, 'bom', 1);
		$items = empty($this->export['header']) ? [] : [$this->export['header']];
		foreach ($this->exportConfig as $format => $setting) {
			$iconOptions = ArrayHelper::getValue($setting, 'iconOptions', []);
			Html::addCssClass($iconOptions, $setting['icon']);
			$label = (empty($setting['icon']) || $setting['icon'] == '') ? $setting['label'] :
				Html::tag('i', '', $iconOptions) . ' ' . $setting['label'];
			$mime = ArrayHelper::getValue($setting, 'mime', 'text/plain');
			$config = ArrayHelper::getValue($setting, 'config', []);
			$cssStyles = ArrayHelper::getValue($setting, 'cssStyles', []);
			if ($format === self::JSON) {
				unset($config['jsonReplacer']);
			}
			$cfg = $this->hashExportConfig ? Json::encode($config) : '';
			$intCfg = empty($this->hashExportConfig) ? 0 : 1;
			$dataToHash = $this->moduleId . $setting['filename'] . $mime . $encoding . $bom . $intCfg . $cfg;
			$hash = Yii::$app->security->hashData($dataToHash, $this->_module->exportEncryptSalt);
			$items[] = [
				'label' => $label,
				'url' => '#',
				'linkOptions' => [
					'class' => 'export-' . $format,
					'data-mime' => $mime,
					'data-hash' => $hash,
					'data-hash-export-config' => $intCfg,
					'data-css-styles' => $cssStyles,
				],
				'options' => $setting['options'],
			];
		}
		$itemsBefore = ArrayHelper::getValue($this->export, 'itemsBefore', []);
		$itemsAfter = ArrayHelper::getValue($this->export, 'itemsAfter', []);
		$items = ArrayHelper::merge($itemsBefore, $items, $itemsAfter);
		$opts = [
			'label' => $title,
			'dropdown' => ['items' => $items, 'encodeLabels' => false, 'options' => $menuOptions],
			'encodeLabel' => false,
		];
		Html::addCssClass($this->exportContainer, 'btn-group');
		if ($isBs4) {
			$opts['buttonOptions'] = $options;
			$opts['renderContainer'] = false;
			/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
			$out = Html::tag('div', \kartik\bs4dropdown\ButtonDropdown::widget($opts), $this->exportContainer);
		} else {
			$opts['options'] = $options;
			$opts['containerOptions'] = $this->exportContainer;
			/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
			$out = \yii\bootstrap\ButtonDropdown::widget($opts);
		}
		return $out;
	}

	public function renderTableHeader()
	{
		$cells = [];
		foreach ($this->columns as $index => $column) {
			/* @var DataColumn $column */
			if ($this->resizableColumns && $this->persistResize) {
				$column->headerOptions['data-resizable-column-id'] = "kv-col-{$index}";
			}
			$cells[] = $column->renderHeaderCell();
		}
		$content = Html::tag('tr', implode('', $cells), $this->headerRowOptions);
		if ($this->filterPosition == self::FILTER_POS_HEADER) {
			$content = $this->renderFilters() . $content;
		} elseif ($this->filterPosition == self::FILTER_POS_BODY) {
			$content .= $this->renderFilters();
		}
		return "<thead>\n" .
			$this->generateRows($this->beforeHeader) . "\n" .
			$content . "\n" .
			$this->generateRows($this->afterHeader) . "\n" .
			'</thead>';
	}

	public function renderTableFooter()
	{
		$content = parent::renderTableFooter();
		return strtr(
			$content,
			[
				'<tfoot>' => "<tfoot>\n" . $this->generateRows($this->beforeFooter),
				'</tfoot>' => $this->generateRows($this->afterFooter) . "\n</tfoot>",
			]
		);
	}

	public function renderColumnGroup()
	{
		$requireColumnGroup = false;
		foreach ($this->columns as $column) {
			/* @var $column Column */
			if (!empty($column->options)) {
				$requireColumnGroup = true;
				break;
			}
		}
		if ($requireColumnGroup) {
			$cols = [];
			foreach ($this->columns as $column) {
				//Skip column with groupedRow
				/** @noinspection PhpUndefinedFieldInspection */
				if (property_exists($column, 'groupedRow') && $column->groupedRow) {
					continue;
				}
				$cols[] = Html::tag('col', '', $column->options);
			}

			return Html::tag('colgroup', implode("\n", $cols));
		} else {
			return false;
		}
	}

	public function renderSummary()
	{
		$count = $this->dataProvider->getCount();
		if ($count <= 0) {
			return '';
		}
		$summaryOptions = $this->summaryOptions;
		$tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');
		$configItems = [
			'item' => $this->itemLabelSingle,
			'items' => $this->itemLabelPlural,
			'items-few' => $this->itemLabelFew,
			'items-many' => $this->itemLabelMany,
			'items-acc' => $this->itemLabelAccusative,
		];
		$pagination = $this->dataProvider->getPagination();
		if ($pagination !== false) {
			$totalCount = $this->dataProvider->getTotalCount();
			$begin = $pagination->getPage() * $pagination->pageSize + 1;
			$end = $begin + $count - 1;
			if ($begin > $end) {
				$begin = $end;
			}
			$page = $pagination->getPage() + 1;
			$pageCount = $pagination->pageCount;
			$configSummary = [
				'begin' => $begin,
				'end' => $end,
				'count' => $count,
				'totalCount' => $totalCount,
				'page' => $page,
				'pageCount' => $pageCount,
			];
			if (($summaryContent = $this->summary) === null) {
				return Html::tag($tag, Yii::t('kvgrid',
					'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.',
					$configSummary + $configItems
				), $summaryOptions);
			}
		} else {
			$begin = $page = $pageCount = 1;
			$end = $totalCount = $count;
			$configSummary = [
				'begin' => $begin,
				'end' => $end,
				'count' => $count,
				'totalCount' => $totalCount,
				'page' => $page,
				'pageCount' => $pageCount,
			];
			if (($summaryContent = $this->summary) === null) {
				return Html::tag($tag,
					Yii::t('kvgrid', 'Total <b>{count, number}</b> {count, plural, one{{item}} other{{items}}}.',
						$configSummary + $configItems
					), $summaryOptions);
			}
		}

		return Yii::$app->getI18n()->format($summaryContent, $configSummary, Yii::$app->language);
	}

	public function renderPager()
    {
        $pagination = $this->dataProvider->getPagination();

        if ($pagination === false || $this->dataProvider->getCount() <= 0) {
            return '';
        }
        /* @var $class LinkPager */
        $pager = $this->pager;
        $class = ArrayHelper::remove($pager, 'class', \yii\widgets\LinkPager::className());
        $pager['pagination'] = $pagination;
        $pager['view'] = $this->getView();

        if($class::widget($pager)){
        	return '<br>' . $class::widget($pager);
        }else{
        	return;
        }
    }

	protected function initModule()
	{
		if (!isset($this->moduleId)) {
			$this->_module = Module::getInstance();
			if (isset($this->_module)) {
				$this->moduleId = $this->_module->id;
				return;
			}
			$this->moduleId = Module::MODULE;
		}
		$this->_module = Config::getModule($this->moduleId, Module::class);
		if (isset($this->bsVersion)) {
			return;
		}
	}

	protected function initExport()
	{
		if ($this->export === false) {
			return;
		}
		$this->exportConversions = array_replace_recursive(
			[
				['from' => self::ICON_ACTIVE, 'to' => Yii::t('kvgrid', 'Active')],
				['from' => self::ICON_INACTIVE, 'to' => Yii::t('kvgrid', 'Inactive')],
			],
			$this->exportConversions
		);
		if (!isset($this->export['fontAwesome'])) {
			$this->export['fontAwesome'] = false;
		}
		$isFa = $this->export['fontAwesome'];
		$isBs4 = $this->isBs4();
		$this->export = array_replace_recursive(
			[
				'label' => '',
				'icon' => $isFa ? 'fa fa-share-square-o' : ($this->isBs4() ? 'fas fa-external-link-alt' : 'glyphicon glyphicon-export'),
				'messages' => [
					'allowPopups' => Yii::t(
						'kvgrid',
						'Disable any popup blockers in your browser to ensure proper download.'
					),
					'confirmDownload' => Yii::t('kvgrid', 'Ok to proceed?'),
					'downloadProgress' => Yii::t('kvgrid', 'Generating the export file. Please wait...'),
					'downloadComplete' => Yii::t(
						'kvgrid',
						'Request submitted! You may safely close this dialog after saving your downloaded file.'
					),
				],
				'options' => ['class' => 'btn ' . $this->_defaultBtnCss, 'title' => Yii::t('kvgrid', 'Export')],
				'menuOptions' => ['class' => 'dropdown-menu dropdown-menu-right '],
				'skipExportElements' => ['.sr-only', '.hide'],
			],
			$this->export
		);
		if (!isset($this->export['header'])) {
			$this->export['header'] = '<li role="presentation" class="dropdown-header">' .
				Yii::t('kvgrid', 'Export Page Data') . '</li>';
		}
		if (!isset($this->export['headerAll'])) {
			$this->export['headerAll'] = '<li role="presentation" class="dropdown-header">' .
				Yii::t('kvgrid', 'Export All Data') . '</li>';
		}
		$title = empty($this->caption) ? Yii::t('kvgrid', 'Grid Export') : $this->caption;
		$pdfHeader = [
			'L' => [
				'content' => Yii::t('kvgrid', 'Yii2 Grid Export (PDF)'),
				'font-size' => 8,
				'color' => '#333333',
			],
			'C' => [
				'content' => $title,
				'font-size' => 16,
				'color' => '#333333',
			],
			'R' => [
				'content' => Yii::t('kvgrid', 'Generated') . ': ' . date('D, d-M-Y'),
				'font-size' => 8,
				'color' => '#333333',
			],
		];
		$pdfFooter = [
			'L' => [
				'content' => Yii::t('kvgrid', '© Krajee Yii2 Extensions'),
				'font-size' => 8,
				'font-style' => 'B',
				'color' => '#999999',
			],
			'R' => [
				'content' => '[ {PAGENO} ]',
				'font-size' => 10,
				'font-style' => 'B',
				'font-family' => 'serif',
				'color' => '#333333',
			],
			'line' => true,
		];
		$cssStyles = [
			'.kv-group-even' => ['background-color' => '#f0f1ff'],
			'.kv-group-odd' => ['background-color' => '#f9fcff'],
			'.kv-grouped-row' => ['background-color' => '#fff0f5', 'font-size' => '1.3em', 'padding' => '10px'],
			'.kv-table-caption' => [
				'border' => '1px solid #ddd',
				'border-bottom' => 'none',
				'font-size' => '1.5em',
				'padding' => '8px',
			],
			'.kv-table-footer' => ['border-top' => '4px double #ddd', 'font-weight' => 'bold'],
			'.kv-page-summary td' => [
				'background-color' => '#ffeeba',
				'border-top' => '4px double #ddd',
				'font-weight' => 'bold',
			],
			'.kv-align-center' => ['text-align' => 'center'],
			'.kv-align-left' => ['text-align' => 'left'],
			'.kv-align-right' => ['text-align' => 'right'],
			'.kv-align-top' => ['vertical-align' => 'top'],
			'.kv-align-bottom' => ['vertical-align' => 'bottom'],
			'.kv-align-middle' => ['vertical-align' => 'middle'],
			'.kv-editable-link' => [
				'color' => '#428bca',
				'text-decoration' => 'none',
				'background' => 'none',
				'border' => 'none',
				'border-bottom' => '1px dashed',
				'margin' => '0',
				'padding' => '2px 1px',
			],
		];
		$defaultExportConfig = [
			self::HTML => [
				'label' => Yii::t('kvgrid', 'HTML'),
				'icon' => $isBs4 ? 'fas fa-file-alt' : ($isFa ? 'fa fa-file-text' : 'glyphicon glyphicon-save'),
				'iconOptions' => ['class' => 'text-info'],
				'showHeader' => true,
				'showPageSummary' => true,
				'showFooter' => true,
				'showCaption' => true,
				'filename' => Yii::t('kvgrid', 'grid-export'),
				'alertMsg' => Yii::t('kvgrid', 'The HTML export file will be generated for download.'),
				'options' => ['title' => Yii::t('kvgrid', 'Hyper Text Markup Language')],
				'mime' => 'text/plain',
				'cssStyles' => $cssStyles,
				'config' => [
					'cssFile' => $this->isBs4() ?
						[
							'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css',
							'https://use.fontawesome.com/releases/v5.3.1/css/all.css',
						] :
						['https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'],
				],
			],
			self::CSV => [
				'label' => Yii::t('kvgrid', 'CSV'),
				'icon' => $isBs4 ? 'fas fa-file-code' : ($isFa ? 'fa fa-file-code-o' : 'glyphicon glyphicon-floppy-open'),
				'iconOptions' => ['class' => 'text-primary'],
				'showHeader' => true,
				'showPageSummary' => true,
				'showFooter' => true,
				'showCaption' => true,
				'filename' => Yii::t('kvgrid', 'grid-export'),
				'alertMsg' => Yii::t('kvgrid', 'The CSV export file will be generated for download.'),
				'options' => ['title' => Yii::t('kvgrid', 'Comma Separated Values')],
				'mime' => 'application/csv',
				'config' => [
					'colDelimiter' => ',',
					'rowDelimiter' => "\r\n",
				],
			],
			self::TEXT => [
				'label' => Yii::t('kvgrid', 'Text'),
				'icon' => $isBs4 ? 'far fa-file-alt' : ($isFa ? 'fa fa-file-text-o' : 'glyphicon glyphicon-floppy-save'),
				'iconOptions' => ['class' => 'text-muted'],
				'showHeader' => true,
				'showPageSummary' => true,
				'showFooter' => true,
				'showCaption' => true,
				'filename' => Yii::t('kvgrid', 'grid-export'),
				'alertMsg' => Yii::t('kvgrid', 'The TEXT export file will be generated for download.'),
				'options' => ['title' => Yii::t('kvgrid', 'Tab Delimited Text')],
				'mime' => 'text/plain',
				'config' => [
					'colDelimiter' => "\t",
					'rowDelimiter' => "\r\n",
				],
			],
			self::EXCEL => [
				'label' => Yii::t('kvgrid', 'Excel'),
				'icon' => $isBs4 ? 'far fa-file-excel' : ($isFa ? 'fa fa-file-excel-o' : 'glyphicon glyphicon-floppy-remove'),
				'iconOptions' => ['class' => 'text-success'],
				'showHeader' => true,
				'showPageSummary' => true,
				'showFooter' => true,
				'showCaption' => true,
				'filename' => Yii::t('kvgrid', 'grid-export'),
				'alertMsg' => Yii::t('kvgrid', 'The EXCEL export file will be generated for download.'),
				'options' => ['title' => Yii::t('kvgrid', 'Microsoft Excel 95+')],
				'mime' => 'application/vnd.ms-excel',
				'cssStyles' => $cssStyles,
				'config' => [
					'worksheet' => Yii::t('kvgrid', 'ExportWorksheet'),
					'cssFile' => '',
				],
			],
			self::PDF => [
				'label' => Yii::t('kvgrid', 'PDF'),
				'icon' => $isBs4 ? 'far fa-file-pdf' : ($isFa ? 'fa fa-file-pdf-o' : 'glyphicon glyphicon-floppy-disk'),
				'iconOptions' => ['class' => 'text-danger'],
				'showHeader' => true,
				'showPageSummary' => true,
				'showFooter' => true,
				'showCaption' => true,
				'filename' => Yii::t('kvgrid', 'grid-export'),
				'alertMsg' => Yii::t('kvgrid', 'The PDF export file will be generated for download.'),
				'options' => ['title' => Yii::t('kvgrid', 'Portable Document Format')],
				'mime' => 'application/pdf',
				'cssStyles' => $cssStyles,
				'config' => [
					'mode' => 'UTF-8',
					'format' => 'A4-L',
					'destination' => 'D',
					'marginTop' => 20,
					'marginBottom' => 20,
					'cssInline' => '.kv-wrap{padding:20px}',
					'methods' => [
						'SetHeader' => [
							['odd' => $pdfHeader, 'even' => $pdfHeader],
						],
						'SetFooter' => [
							['odd' => $pdfFooter, 'even' => $pdfFooter],
						],
					],
					'options' => [
						'title' => $title,
						'subject' => Yii::t('kvgrid', 'PDF export generated by kartik-v/yii2-grid extension'),
						'keywords' => Yii::t('kvgrid', 'krajee, grid, export, yii2-grid, pdf'),
					],
					'contentBefore' => '',
					'contentAfter' => '',
				],
			],
			self::JSON => [
				'label' => Yii::t('kvgrid', 'JSON'),
				'icon' => $isBs4 ? 'far fa-file-code' : ($isFa ? 'fa fa-file-code-o' : 'glyphicon glyphicon-floppy-open'),
				'iconOptions' => ['class' => 'text-warning'],
				'showHeader' => true,
				'showPageSummary' => true,
				'showFooter' => true,
				'showCaption' => true,
				'filename' => Yii::t('kvgrid', 'grid-export'),
				'alertMsg' => Yii::t('kvgrid', 'The JSON export file will be generated for download.'),
				'options' => ['title' => Yii::t('kvgrid', 'JavaScript Object Notation')],
				'mime' => 'application/json',
				'config' => [
					'colHeads' => [],
					'slugColHeads' => false,
					'jsonReplacer' => new JsExpression("function(k,v){return typeof(v)==='string'?$.trim(v):v}"),
					'indentSpace' => 4,
				],
			],
		];

		// Remove PDF if dependency is not loaded.
		if (!class_exists('\\kartik\\mpdf\\Pdf')) {
			unset($defaultExportConfig[self::PDF]);
		}

		$this->exportConfig = self::parseExportConfig($this->exportConfig, $defaultExportConfig);
	}

	protected function initToggleData()
	{
		if (!$this->toggleData) {
			return;
		}
		$isBs4 = $this->isBs4();
		$defBtnCss = 'btn ' . $this->_defaultBtnCss;
		$defaultOptions = [
			'maxCount' => 10000,
			'minCount' => 500,
			'confirmMsg' => Yii::t(
				'kvgrid',
				'There are {totalCount} records. Are you sure you want to display them all?',
				['totalCount' => number_format($this->dataProvider->getTotalCount())]
			),
			'all' => [
				'icon' => $isBs4 ? 'fas fa-expand' : 'glyphicon glyphicon-resize-full',
				'label' => Yii::t('kvgrid', 'All'),
				'class' => $defBtnCss,
				'title' => Yii::t('kvgrid', 'Show all data'),
			],
			'page' => [
				'icon' => $isBs4 ? 'fas fa-compress' : 'glyphicon glyphicon-resize-small',
				'label' => Yii::t('kvgrid', 'Page'),
				'class' => $defBtnCss,
				'title' => Yii::t('kvgrid', 'Show first page data'),
			],
		];
		$this->toggleDataOptions = array_replace_recursive($defaultOptions, $this->toggleDataOptions);
		$tag = $this->_isShowAll ? 'page' : 'all';
		$options = $this->toggleDataOptions[$tag];
		$this->toggleDataOptions[$tag]['id'] = $this->_toggleButtonId;
		$icon = ArrayHelper::remove($this->toggleDataOptions[$tag], 'icon', '');
		$label = !isset($options['label']) ? $defaultOptions[$tag]['label'] : $options['label'];
		if (!empty($icon)) {
			$label = "<i class='{$icon}'></i> " . $label;
		}
		$this->toggleDataOptions[$tag]['label'] = $label;
		if (!isset($this->toggleDataOptions[$tag]['title'])) {
			$this->toggleDataOptions[$tag]['title'] = $defaultOptions[$tag]['title'];
		}
		$this->toggleDataOptions[$tag]['data-pjax'] = $this->pjax ? 'true' : false;
	}

	protected function initBootstrapStyle()
	{
		Html::addCssClass($this->tableOptions, 'kv-grid-table');
		if (!$this->bootstrap) {
			return;
		}
		Html::addCssClass($this->tableOptions, 'table');
		if ($this->hover) {
			Html::addCssClass($this->tableOptions, 'table-hover');
		}
		if ($this->bordered) {
			Html::addCssClass($this->tableOptions, 'table-bordered');
		}
		if ($this->striped) {
			Html::addCssClass($this->tableOptions, 'table-striped');
		}
		if ($this->condensed) {
			$this->addCssClass($this->tableOptions, self::BS_TABLE_CONDENSED);
		}
		if ($this->floatHeader) {
			if ($this->perfectScrollbar) {
				$this->floatOverflowContainer = true;
			}
			if ($this->floatOverflowContainer) {
				$this->responsive = false;
				Html::addCssClass($this->containerOptions, 'kv-grid-wrapper');
			}
		}
		if ($this->responsive) {
			Html::addCssClass($this->containerOptions, 'table-responsive');
		}
		if ($this->responsiveWrap) {
			Html::addCssClass($this->tableOptions, 'kv-table-wrap');
		}
	}

	protected function initHeader()
	{
		if ($this->filterPosition === self::FILTER_POS_HEADER) {
			// Float header plugin misbehaves when filter is placed on the first row.
			// So disable it when `filterPosition` is `header`.
			$this->floatHeader = false;
		}
	}

	protected function initLayout()
	{
		Html::addCssClass($this->filterRowOptions, 'skip-export');
		if ($this->resizableColumns && $this->persistResize) {
			$key = empty($this->resizeStorageKey) ? Yii::$app->user->id : $this->resizeStorageKey;
			$gridId = empty($this->options['id']) ? $this->getId() : $this->options['id'];
			$this->containerOptions['data-resizable-columns-id'] = (empty($key) ? "kv-{$gridId}" : "kv-{$key}-{$gridId}");
		}
		if ($this->hideResizeMobile) {
			Html::addCssClass($this->options, 'hide-resize');
		}
		$this->replaceLayoutTokens([
			'{toolbarContainer}' => $this->renderToolbarContainer(),
			'{toolbar}' => $this->renderToolbar(),
			'{export}' => $this->renderExport(),
			'{toggleData}' => $this->renderToggleData(),
			'{items}' => Html::tag('div', '{items}', $this->containerOptions),
		]);
		if (is_array($this->replaceTags) && !empty($this->replaceTags)) {
			foreach ($this->replaceTags as $key => $value) {
				if ($value instanceof \Closure) {
					$value = call_user_func($value, $this);
				}
				$this->layout = str_replace($key, $value, $this->layout);
			}
		}
	}

	protected function replaceLayoutTokens($pairs)
	{
		foreach ($pairs as $token => $replace) {
			if (strpos($this->layout, $token) !== false) {
				$this->layout = str_replace($token, $replace, $this->layout);
			}
		}
	}

	protected function beginPjax()
	{
		$view = $this->getView();

        $container = 'var reload = false;';

        $container .= "$('$this->filterSelector').on('change',function(){
            setTimeout(function(){
                location.reload();
                reload = true;
            },0)
        });";


		$container .= 'jQuery("#' . $this->pjaxSettings['options']['id'] . '")';
		$js = $container;
		if (ArrayHelper::getValue($this->pjaxSettings, 'neverTimeout', true)) {
			$js .= ".on('pjax:timeout', function(e){e.preventDefault()})";
		}
		$loadingCss = ArrayHelper::getValue($this->pjaxSettings, 'loadingCssClass', 'kv-grid-loading');
		$postPjaxJs = "setTimeout({$this->_gridClientFunc}, 2500);";
		$pjaxCont = '$("#' . $this->pjaxSettings['options']['id'] . '")';
		if ($loadingCss !== false) {
			if ($loadingCss === true) {
				$loadingCss = 'kv-grid-loading';
			}
			$js .= ".on('pjax:send', function(){if(!reload){{$pjaxCont}.addClass('{$loadingCss}')}})";
			$postPjaxJs .= "{$pjaxCont}.removeClass('{$loadingCss}');";
		}
		$postPjaxJs .= "\n" . $this->_toggleScript;
		if (!empty($postPjaxJs)) {
			$event = 'pjax:complete.' . hash('crc32', $postPjaxJs);
			$js .= ".off('{$event}').on('{$event}', function(){if(!reload){{$postPjaxJs}}})";
		}
		if ($js != $container) {
			$view->registerJs("{$js};");
		}
		Pjax::begin($this->pjaxSettings['options']);

        $switcher = "switcher('.switch')";
        $view->registerJs("{$switcher};");

		echo '<div class="kv-loader-overlay"><div class="kv-loader"></div></div>';
		echo ArrayHelper::getValue($this->pjaxSettings, 'beforeGrid', '');
	}

	protected function endPjax()
	{
		echo ArrayHelper::getValue($this->pjaxSettings, 'afterGrid', '');
		Pjax::end();
	}

	protected function renderToolbar()
	{
		if (empty($this->toolbar) || (!is_string($this->toolbar) && !is_array($this->toolbar))) {
			return '';
		}
		if (is_string($this->toolbar)) {
			return $this->toolbar;
		}
		$toolbar = '';
		foreach ($this->toolbar as $item) {
			if (is_array($item)) {
				$content = ArrayHelper::getValue($item, 'content', '');
				$options = ArrayHelper::getValue($item, 'options', []);
				static::initCss($options, 'btn-group');
				$toolbar .= Html::tag('div', $content, $options);
			} else {
				$toolbar .= "\n{$item}";
			}
		}
		return $toolbar;
	}

	protected function renderToolbarContainer()
	{
		$tag = ArrayHelper::remove($this->toolbarContainerOptions, 'tag', 'div');

		/**
		 * allow to override the float declaration:
		 * forcing float-right only if no float is defined in toolbarContainerOptions
		 */
		if (
			!strpos($this->toolbarContainerOptions['class'], $this->getCssClass(self::BS_PULL_RIGHT))
			&& !strpos($this->toolbarContainerOptions['class'], $this->getCssClass(self::BS_PULL_LEFT))
		) {
			$this->addCssClass($this->toolbarContainerOptions, self::BS_PULL_RIGHT);
		}

		return Html::tag($tag, $this->renderToolbar(), $this->toolbarContainerOptions);
	}

	protected function generateRows($data)
	{
		if (empty($data)) {
			return '';
		}
		if (is_string($data)) {
			return $data;
		}
		$rows = '';
		if (is_array($data)) {
			foreach ($data as $row) {
				if (empty($row['columns'])) {
					continue;
				}
				$rowOptions = ArrayHelper::getValue($row, 'options', []);
				$rows .= Html::beginTag('tr', $rowOptions);
				foreach ($row['columns'] as $col) {
					$colOptions = ArrayHelper::getValue($col, 'options', []);
					$colContent = ArrayHelper::getValue($col, 'content', '');
					$tag = ArrayHelper::getValue($col, 'tag', 'th');
					$rows .= "\t" . Html::tag($tag, $colContent, $colOptions) . "\n";
				}
				$rows .= Html::endTag('tr') . "\n";
			}
		}
		return $rows;
	}

	protected function genToggleDataScript()
	{
		$this->_toggleScript = '';
		if (!$this->toggleData) {
			return;
		}
		$minCount = ArrayHelper::getValue($this->toggleDataOptions, 'minCount', 0);
		if (!$minCount || $minCount >= $this->dataProvider->getTotalCount()) {
			return;
		}
		$view = $this->getView();
		$opts = Json::encode(
			[
				'id' => $this->_toggleButtonId,
				'pjax' => $this->pjax ? 1 : 0,
				'mode' => $this->_isShowAll ? 'all' : 'page',
				'msg' => ArrayHelper::getValue($this->toggleDataOptions, 'confirmMsg', ''),
				'lib' => new JsExpression(
					ArrayHelper::getValue($this->krajeeDialogSettings, 'libName', 'krajeeDialog')
				),
			]
		);
		$this->_toggleOptionsVar = 'kvTogOpts_' . hash('crc32', $opts);
		$view->registerJs("{$this->_toggleOptionsVar}={$opts};");
		GridToggleDataAsset::register($view);
		$this->_toggleScript = "kvToggleData({$this->_toggleOptionsVar});";
	}

	protected function registerAssets()
	{
		$view = $this->getView();

        SwitcherAsset::register($view);

		$script = '';
		if ($this->bootstrap) {
			GridViewAsset::register($view);
		}
		Dialog::widget($this->krajeeDialogSettings);
		$gridId = $this->options['id'];
		$NS = '.' . str_replace('-', '_', $gridId);
		if ($this->export !== false && is_array($this->export) && !empty($this->export)) {
			GridExportAsset::register($view);
			if (!isset($this->_module->downloadAction)) {
				$action = ["/{$this->moduleId}/export/download"];
			} else {
				$action = (array)$this->_module->downloadAction;
			}
			$gridOpts = Json::encode(
				[
					'gridId' => $gridId,
					'action' => Url::to($action),
					'module' => $this->moduleId,
					'encoding' => ArrayHelper::getValue($this->export, 'encoding', 'utf-8'),
					'bom' => (int)ArrayHelper::getValue($this->export, 'bom', 1),
					'target' => ArrayHelper::getValue($this->export, 'target', self::TARGET_BLANK),
					'messages' => $this->export['messages'],
					'exportConversions' => $this->exportConversions,
					'skipExportElements' => $this->export['skipExportElements'],
					'showConfirmAlert' => ArrayHelper::getValue($this->export, 'showConfirmAlert', true),
				]
			);
			$gridOptsVar = 'kvGridExp_' . hash('crc32', $gridOpts);
			$view->registerJs("var {$gridOptsVar}={$gridOpts};");
			foreach ($this->exportConfig as $format => $setting) {
				$id = "jQuery('#{$gridId} .export-{$format}')";
				$genOpts = Json::encode(
					[
						'filename' => $setting['filename'],
						'showHeader' => $setting['showHeader'],
						'showPageSummary' => $setting['showPageSummary'],
						'showFooter' => $setting['showFooter'],
					]
				);
				$genOptsVar = 'kvGridExp_' . hash('crc32', $genOpts);
				$view->registerJs("var {$genOptsVar}={$genOpts};");
				$expOpts = Json::encode(
					[
						'dialogLib' => ArrayHelper::getValue($this->krajeeDialogSettings, 'libName', 'krajeeDialog'),
						'gridOpts' => new JsExpression($gridOptsVar),
						'genOpts' => new JsExpression($genOptsVar),
						'alertMsg' => ArrayHelper::getValue($setting, 'alertMsg', false),
						'config' => ArrayHelper::getValue($setting, 'config', []),
					]
				);
				$expOptsVar = 'kvGridExp_' . hash('crc32', $expOpts);
				$view->registerJs("var {$expOptsVar}={$expOpts};");
				$script .= "{$id}.gridexport({$expOptsVar});";
			}
		}
		$contId = '#' . $this->containerOptions['id'];
		$container = "jQuery('{$contId}')";
		if ($this->resizableColumns) {
			$rcDefaults = [];
			if ($this->persistResize) {
				GridResizeStoreAsset::register($view);
			} else {
				$rcDefaults = ['store' => null];
			}
			$rcOptions = Json::encode(array_replace_recursive($rcDefaults, $this->resizableColumnsOptions));
			GridResizeColumnsAsset::register($view);
			$script .= "{$container}.resizableColumns('destroy').resizableColumns({$rcOptions});";
		}
		if ($this->floatHeader) {
			GridFloatHeadAsset::register($view);
			// fix floating header for IE browser when using group grid functionality
			$skipCss = '.kv-grid-group-row,.kv-group-header,.kv-group-footer'; // skip these CSS for IE
			$js = 'function($table){return $table.find("tbody tr:not(' . $skipCss . '):visible:first>*");}';
			$opts = [
				'floatTableClass' => 'kv-table-float',
				'floatContainerClass' => 'kv-thead-float',
				'getSizingRow' => new JsExpression($js),
			];
			if ($this->floatOverflowContainer) {
				$opts['scrollContainer'] = new JsExpression("function(){return {$container};}");
			}
			$this->floatHeaderOptions = array_replace_recursive($opts, $this->floatHeaderOptions);
			$opts = Json::encode($this->floatHeaderOptions);
			$script .= "jQuery('#{$gridId} .kv-grid-table:first').floatThead({$opts});";
			// integrate resizeableColumns with floatThead
			if ($this->resizableColumns) {
				$script .= "{$container}.off('{$NS}').on('column:resize{$NS}', function(e){" .
					"jQuery('#{$gridId} .kv-grid-table:nth-child(2)').floatThead('reflow');" .
					'});';
			}
		}
		$psVar = 'ps_' . Inflector::slug($this->containerOptions['id'], '_');
		if ($this->perfectScrollbar) {
			GridPerfectScrollbarAsset::register($view);
			$script .= "var {$psVar} = new PerfectScrollbar('{$contId}', " .
				Json::encode($this->perfectScrollbarOptions) . ');';
		}
		$this->genToggleDataScript();
		$script .= $this->_toggleScript;
		$this->_gridClientFunc = 'kvGridInit_' . hash('crc32', $script);
		$this->options['data-krajee-grid'] = $this->_gridClientFunc;
		$this->options['data-krajee-ps'] = $psVar;
		$view->registerJs("var {$this->_gridClientFunc}=function(){\n{$script}\n};\n{$this->_gridClientFunc}();");
	}
}
