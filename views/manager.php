<?php
/**
 * @var \yii\web\View $this
 * @var array $options

 */
use lodovo\elfinder\Assets;
use yii\helpers\Json;


Assets::register($this);
Assets::addLangFile($options['lang'], $this);

if(!empty($options['noConflict']))
	Assets::noConflict($this);

unset($options['noConflict']);


$this->registerJs("
    var FileBrowserDialogue = {
    init: function() {
      // Here goes your code for setting your custom things onLoad.
    },
    mySubmit: function (file, elf) {
      // pass selected file data to TinyMCE
      parent.tinymce.activeEditor.windowManager.getParams().oninsert(file, elf);
      // close popup window
      parent.tinymce.activeEditor.windowManager.close();
    }
  }
function ElFinderGetCommands(disabled){
    var Commands = elFinder.prototype._options.commands;
    $.each(disabled, function(i, cmd) {
        (idx = $.inArray(cmd, Commands)) !== -1 && Commands.splice(idx,1);
    });
    return Commands;
}

    var winHashOld = '';
    function elFinderFullscreen(){

        var width = $(window).width()-($('#elfinder').outerWidth(true) - $('#elfinder').width());
        var height = $(window).height()-($('#elfinder').outerHeight(true) - $('#elfinder').height());

        var el = $('#elfinder').elfinder('instance');

        var winhash = $(window).width() + '|' + $(window).height();


        if(winHashOld == winhash)
            return;

        winHashOld = winhash;

        el.resize(width, height);
    }

    $('#elfinder').elfinder(".Json::encode($options).").elfinder('instance');

    $(window).resize(elFinderFullscreen);

    elFinderFullscreen();
    ");


$this->registerCss("
html, body {
    height: 100%;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
    position: relative;
    padding: 0; margin: 0;
}
");




?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>elFinder 2.0</title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div id="elfinder"></div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>