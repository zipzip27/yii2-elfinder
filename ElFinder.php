<?php
/**
 * Date: 22.01.14
 * Time: 23:44
 */

namespace lodovo\elfinder;

use Yii;
use yii\base\Widget as BaseWidjet;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class Widget
 * @package mihaildev\elfinder
 */

class ElFinder extends BaseWidjet{

	public $language;

	public $filter;

	public $callbackFunction;

	public $multiple = false;
	
	public $path;// work with PathController
	public $startPath;

	public $containerOptions = [];
	public $frameOptions = [];
	public $controller = 'elfinder';

	public static function genPathHash($path)
	{
		if(DIRECTORY_SEPARATOR != '/'){
			$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		}

		if(preg_match('/^@(\d+)/', $path, $match)){
			$volume = $match[1];
			$path = ltrim(substr($path, strlen($match[0])), DIRECTORY_SEPARATOR);
			if(empty($path)){
				$path = DIRECTORY_SEPARATOR;
			}
		}else{
			$volume = 1;
		}
		$hash = rtrim(strtr(base64_encode($path), '+/=', '-_.'), '.');
		return 'elf_l' . $volume .'_' . $hash;
	}

	public static function getManagerUrl($controller, $params = [])
	{
		$params[0] = '/'.$controller."/manager";
		return Yii::$app->urlManager->createUrl($params);
	}
	public static function tinyMceOptions($controller, $options = []){
		if(is_array($controller)){
			$id = $controller[0];
			unset($controller[0]);
			$params = $controller;
		}else{
			$id = $controller;
			$params = [];
		}
		
		if(isset($params['startPath'])){
			$params['#'] = ElFinder::genPathHash($params['startPath']);
			unset($params['startPath']);
		}

		$file= self::getManagerUrl($id, ArrayHelper::merge($params, ['filter'=>'image','tinymce'=>true]));
		$js = "
		function elFinderBrowser(callback, value, meta) {
		  tinymce.activeEditor.windowManager.open({
		    file: '$file',// use an absolute path!
		    title: 'elFinder 2.0',
		    width: 900,  
		    height: 450,
		    resizable: 'yes'
		  }, {
		    oninsert: function (file, elf) {
		      var url, reg, info;

		      // URL normalization
		      url = file.url;
		      reg = /\/[^/]+?\/\.\.\//;
		      while(url.match(reg)) {
		        url = url.replace(reg, '/');
		      }

		      // Make file info
		      info = file.name + ' (' + elf.formatSize(file.size) + ')';

		      // Provide file and text for the link dialog
		      if (meta.filetype == 'file') {
		        callback(url, {text: info, title: info});
		      }

		      // Provide image and alt text for the image dialog
		      if (meta.filetype == 'image') {
		        callback(url, {alt: info});
		      }

		      // Provide alternative source and posted for the media dialog
		      if (meta.filetype == 'media') {
		        callback(url);
		      }
		    }
		  });
		  return false;
		}";
		Yii::$app->view->registerJs($js, \yii\web\View::POS_END);
		/* return ArrayHelper::merge([
				'filebrowserBrowseUrl' => self::getManagerUrl($id, $params),
				'filebrowserImageBrowseUrl' => self::getManagerUrl($id, ArrayHelper::merge($params, ['filter'=>'image'])),
				'filebrowserFlashBrowseUrl' => self::getManagerUrl($id, ArrayHelper::merge($params, ['filter'=>'flash'])),
		], $options); */
		return new \yii\web\JsExpression('elFinderBrowser');
	}
	public static function ckeditorOptions($controller, $options = []){

		if(is_array($controller)){
			$id = $controller[0];
			unset($controller[0]);
			$params = $controller;
		}else{
			$id = $controller;
			$params = [];
		}

		if(isset($params['startPath'])){
			$params['#'] = ElFinder::genPathHash($params['startPath']);
			unset($params['startPath']);
		}

		return ArrayHelper::merge([
			'filebrowserBrowseUrl' => self::getManagerUrl($id, $params),
			'filebrowserImageBrowseUrl' => self::getManagerUrl($id, ArrayHelper::merge($params, ['filter'=>'image'])),
			'filebrowserFlashBrowseUrl' => self::getManagerUrl($id, ArrayHelper::merge($params, ['filter'=>'flash'])),
		], $options);
	}

	public function init()
	{
		if(empty($this->language))
			$this->language = self::getSupportedLanguage(Yii::$app->language);

		$managerOptions = [];
		if(!empty($this->filter))
			$managerOptions['filter'] = $this->filter;

		if(!empty($this->callbackFunction))
			$managerOptions['callback'] = $this->id;

		if(!empty($this->language))
			$managerOptions['lang'] = $this->language;

		if(!empty($this->path))
			$managerOptions['path'] = $this->path;

		if(!empty($this->startPath))
			$managerOptions['#'] = ElFinder::genPathHash($this->startPath);

		if($this->multiple)
			$managerOptions['multiple'] = $this->multiple;
			
		$this->frameOptions['src'] = $this->getManagerUrl($this->controller, $managerOptions);

		if(!isset($this->frameOptions['style'])){
			$this->frameOptions['style'] = "width: 100%; height: 100%; border: 0;";
		}
	}

	static function getSupportedLanguage($language)
	{
		$supportedLanguages = array('bg', 'jp', 'sk', 'cs', 'ko', 'th', 'de', 'lv', 'tr', 'el', 'nl', 'uk',
			'es', 'no', 'vi', 'fr', 'pl', 'zh_CN', 'hr', 'pt_BR', 'zh_TW', 'hu', 'ro', 'it', 'ru', 'en');

		if(!in_array($language, $supportedLanguages)){
			if (strpos($language, '-')){
				$language = str_replace('-', '_', $language);
				if(!in_array($language, $supportedLanguages)) {
					$language = substr($language, 0, strpos($language, '_'));
					if (!in_array($language, $supportedLanguages))
						$language = false;
				}
			} else {
				$language = false;
			}
		}

		return $language;
	}

	public function run()
	{
		$container = 'div';
		if(isset($this->containerOptions['tag'])){
			$container = $this->containerOptions['tag'];
			unset($this->containerOptions['tag']);
		}

		echo Html::tag($container, Html::tag('iframe','', $this->frameOptions), $this->containerOptions);

		if(!empty($this->callbackFunction)){
			AssetsCallBack::register($this->getView());
			$this->getView()->registerJs("mihaildev.elFinder.register(".Json::encode($this->id).",".Json::encode($this->callbackFunction).");");
		}
	}
}
