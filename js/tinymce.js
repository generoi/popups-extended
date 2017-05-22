(function ($) {
  'use strict';

  function insertLink(editor, type) {
    var text = editor.selection.getContent({'format': 'html'});
    var node = editor.selection.getNode();
    var cssClass = 'spu-' + type + '-link';
    var $node = $(node);

    editor.focus();
    // If it's a link, add the class to the node.
    if ($node.is('a, button')) {
      $node.addClass(cssClass);
      editor.selection.setNode($node[0]);
    }
    // If it's a selection, wrap it in a link.
    else if (text.length) {
      editor.selection.setContent('<a href="#" class="' + cssClass + '">' + text + '</a>');
    }
    else {
      editor.insertContent('<a href="#" class="' + cssClass + '">' + type + '</a>');
    }
  }

  tinymce.PluginManager.add('popups_extended', function (editor, url) {
    editor.addButton('popups_extended_convert_button', {
      title: 'Add Popup Conversion Link',
      icon: 'plus',
      onclick: function(e) {
        insertLink(editor, 'convert');
      }
    });
    editor.addButton('popups_extended_close_button', {
      title: 'Add Popup Close link',
      icon: 'minus',
      onclick: function() {
        insertLink(editor, 'close');
      }
    });
  });
})(jQuery);
