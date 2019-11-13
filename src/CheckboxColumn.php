<?php
namespace kilyakus\widget\grid;

use Closure;
use yii\grid\CheckboxColumn as YiiCheckboxColumn;
use yii\helpers\Html;
use yii\helpers\Json;

class CheckboxColumn extends YiiCheckboxColumn
{
    use ColumnTrait;

    public $rowHighlight = true;
    public $rowSelectedClass;
    public $attribute;
    public $cssClass = 'switch';

    public function init()
    {
        $this->initColumnSettings([
            'hiddenFromExport' => true,
            'mergeHeader' => true,
            'hAlign' => GridView::ALIGN_CENTER,
            'vAlign' => GridView::ALIGN_MIDDLE,
            'width' => '50px'
        ]);
        if (!isset($this->rowSelectedClass)) {
            $this->rowSelectedClass = $this->grid->getCssClass(GridView::BS_TABLE_DANGER);
        }
        $id = $this->grid->options['id'];
        $view = $this->grid->getView();
        CheckboxColumnAsset::register($view);
        if ($this->rowHighlight) {
            Html::addCssClass($this->headerOptions, 'kv-all-select');
            $this->_clientScript = "kvSelectRow('{$id}', '{$this->rowSelectedClass}');";
            $view->registerJs($this->_clientScript);
        }
        $this->parseFormat();
        $this->parseVisibility();
        parent::init();
        $this->setPageRows();
        $opts = Json::encode(
            [
                'name' => $this->name,
                'multiple' => $this->multiple,
                'checkAll' => $this->grid->showHeader ? $this->getHeaderCheckBoxName() : null,
            ]
        );
        $this->_clientScript .= "\nkvSelectColumn('{$id}', {$opts});";
    }

    public function renderDataCell($model, $key, $index)
    {
        $options = $this->fetchContentOptions($model, $key, $index);
        if ($this->rowHighlight) {
            Html::addCssClass($options, 'kv-row-select');
        }
        $this->initPjax($this->_clientScript);
        if ($this->attribute !== null) {
            $this->name = Html::getInputName($model, "[{$index}]{$this->attribute}");
             if (!$this->checkboxOptions instanceof Closure) {
                $this->checkboxOptions['value'] = Html::getAttributeValue($model, $this->attribute);
             }
        }
        return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
    }
}
