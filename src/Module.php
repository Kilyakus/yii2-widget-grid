<?php
namespace kilyakus\widget\grid;

/**
 * This module allows global level configurations for the enhanced Krajee [[GridView]]. One can configure the module
 * in their Yii configuration file as shown below:
 *
 * ```php
 * 'modules' => [
 *     'gridview' => [
 *          'class' => 'kilyakus\widget\grid\Module',
 *          'downloadAction' => '/gridview/export/download' // your grid export download setting
 *     ]
 * ]
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since  1.0
 */
class Module extends \kilyakus\widgets\Module
{
    /**
     * The module name for Krajee gridview
     */
    const MODULE = "gridview";

    /**
     * @var string a random salt that will be used to generate a hash string for export configuration.
     */
    public $exportEncryptSalt = 'SET_A_SALT_FOR_YII2_GRID';

    /**
     * @var string|array the action (url) used for downloading exported file
     */
    public $downloadAction;

    /**
     * @inheritdoc
     */
    protected $_msgCat = 'kvgrid';
}
