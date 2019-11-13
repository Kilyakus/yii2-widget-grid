<?php

namespace kilyakus\widget\grid;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use kilyakus\select2\Select2;

class PageSizer extends Widget
{
    public $pageSizes = [5 => 5, 10 => 10, 20 => 20, 50 => 50];

    public function run()
    {
        $size = 20;
        $saved = Yii::$app->session->get('per-page');
        if (in_array($saved, $this->pageSizes)) {
            $size = $saved;
        }
        $selected = Yii::$app->request->get('per-page');
        if (in_array($selected, $this->pageSizes)) {
            $size = $selected;
        }

        Yii::$app->session->set('per-page', $size);

        return Html::tag('div', Select2::widget([
            'id' => 'per-page',
            'name' => 'per-page',
            'data' => $this->pageSizes,
            'value' => $size,
            'hideSearch' => true,
        ]), ['class' => 'pull-left', 'style' => 'min-width:60px;margin-right:0.7rem;']);
    }
}
