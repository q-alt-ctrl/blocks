jQuery(document).ready(function($) {
  let errorModal;

  const editorCssUrls = [
    '/wp-content/themes/custom-2019/css/base.css',
    '/wp-content/plugins/custom-conference/css/autoload/buttons.css',
    '/wp-content/themes/power-meet/css/conference-child.css',
  ];

  // join the array elements into a comma-separated string
  const editorCssString = editorCssUrls.join(',');

  // add the checkbox to the admin bar
  let checkboxHtml = `<input type="checkbox"${$.cookie('block_debug') ? ' checked' : ''}> `;
  $('#wp-admin-bar-block_debug div').wrapInner('<span/>').prepend(checkboxHtml);
  $('#wp-admin-bar-block_debug_two a').prepend(checkboxHtml);

  // make the menu item click the new checkbox instead.
  $(document).on('click', '#wp-admin-bar-block_debug span, #wp-admin-bar-block_debug_two a', (e) => {
    e.preventDefault();
    $('#wp-admin-bar-block_debug input, #wp-admin-bar-block_debug_two input').first().click();
  });

  // set the cookie on change
  $(document).on('change', '#wp-admin-bar-block_debug input, #wp-admin-bar-block_debug_two input', function(e) {
    e.preventDefault();
    const isChecked = $(this).is(':checked');
    if (isChecked) {
      $.cookie('block_debug', '1', {expires: 7, path: '/'});
    } else {
      $.removeCookie('block_debug', {path: '/'});
    }
    window.location.reload(false);
  });

  /* delegate click event to dynamically added elements as well.
   * This is necessary because the .editing class is added dynamically
   * to the block link, and we need to prevent the default action
   */
  $(document).on('click', '.component-link', function(e) {
    // check if there is an .editing class within this link
    if ($(this).find('.editing').length > 0) {
      // prevent default action if .editing class is found
      e.preventDefault();
    }
  });

  function block_edit_close_modal() {
    $('body').removeClass('block-editing');
    if(typeof(tinyMCE) != "undefined") tinymce.EditorManager.execCommand('mceRemoveEditor', true, 'block_content');
    $('.block-edit-modal-wrapper').fadeOut('fast', function () {
      $(this).remove();
    });
  }

  //save the modal form
  $(document).on('click', '.block-edit-trigger', function (e) {
    e.preventDefault();
    $('.spinner-border').show();
    $('.bi-check-lg').hide();
    tinyMCE.triggerSave();
    const data = {
      action: 'edit_block',
      form: $('.block-edit form').serialize(),
      security: customBlocks.nonce
    };
    $.post(customBlocks.ajaxurl, data, function (response) {
      const responseData = jQuery.parseJSON(response);
      $('.spinner-border').hide();
      $('.bi-check-lg').show();
      
      $(`[data-block-title=${responseData.post_id}]`).removeClass('editing');
      $(`[data-block-content=${responseData.post_id}]`).removeClass('editing');
      $(`[data-block-title=${responseData.post_id}]`).trigger('classChange');
      $(`[data-block-content=${responseData.post_id}]`).trigger('classChange');

      $(`[data-block-title=${responseData.post_id}]`).html(responseData.block_title);
      $(`[data-block-content=${responseData.post_id}]`).html(responseData.block_content);
      block_edit_close_modal();
    });
  });

  //close the modal form with the close link
  $(document).on('click', '.block-edit-close', function (e) {
    e.preventDefault(); // prevent default action
    e.stopPropagation(); // stop event propagation

    const postId = $(this).closest('.block-edit').data('post-id');
    const editType = $(this).closest('.block-edit').data('edit-type');

    // remove the .editing class immediately before closing the editor
    if (editType === 'block') {
      $(`[data-block-content=${postId}]`).removeClass('editing').trigger('classChange');
    } else {
      $(`[data-block-title=${postId}]`).removeClass('editing').trigger('classChange');
    }

    block_edit_close_modal();
  });

  // close the modal with escape key
  $(document).keyup(function(e) {
    if (e.keyCode === 27) { // escape key
      var activeBlock = $('.block-edit.editing');
      if (activeBlock.length) {
        var postId = activeBlock.data('post-id');
        var editType = activeBlock.data('edit-type');
        closeInlineEditor(postId, editType);
      }
    }
  });
  
  // thumbnail
  if (typeof wp === 'object' && typeof wp.media === 'function' && wp.media !== null && typeof wp.media.editor !== 'undefined') {
    let customMedia = true,
        origSendAttachment = wp.media.editor.send.attachment;

    $(document).on('click', '#block_image_trigger', function(e) {
      const button = $(this);
      const id = button.attr('id').replace('_trigger', '');
      customMedia = true;

      wp.media.editor.send.attachment = function(props, attachment) {
        if (customMedia) {
          $(`#${id}_preview`).attr('src', attachment.url).css('height', 'auto').parent().removeClass('hide');
          $(`#${id}_remove`).removeClass('hide');
          $(`#${id}_id`).val(attachment.id);
        } else {
          return origSendAttachment.apply(this, [props, attachment]);
        }
      };

      wp.media.editor.open(button);
      return false;
    });

    $('.add_media').on('click', () => { customMedia = false; });

    // remove the image
    $(document).on('click', '#block_image_remove', function() {
      const id = $(this).attr('id').replace('_remove', '');
      $(`#${id}`).val('');
      $(`#${id}_preview`).attr('src', $(`#${id}_preview`).attr('data-default-src')).css('height', '20px');
      $(`#${id}_id`).val('');
      $(`#${id}_preview`).parent().addClass('hide');
      $(this).addClass('hide');
    });
  }

  const enhanceEditBlock = () => {
    $('.edit_block').each(function() {
      const $parent = $(this).parent();

      if ($parent.is('p')) {
        $parent.parent().addClass('is-editable');
      } else {
        $parent.addClass('is-editable');
      }
    });
  };

  enhanceEditBlock();

  const updateEditLinkVisibility = () => {
    $('.edit_block').each(function() {
      const $parent = $(this).parent();

      // check if the previous sibling has the 'editing' class
      if ($parent.is('p') && $parent.prev().hasClass('editing')) {
        $parent.hide();
      } else if ($(this).prev().hasClass('editing')) {
        $(this).hide();
      } else {
        $parent.show();
        $(this).show();
      }
    });
  };

  $(document).on('classChange', '.is-editable > div', () => {
    updateEditLinkVisibility();
  });

  const adjustInlinePosition = (element) => {
    const $editingBlock = $(element);

    if ($editingBlock.width() <= 200) {
      $editingBlock.find('#block_image_trigger').text('Choose');
      $editingBlock.find('#block_image_remove').text('Remove');
      $editingBlock.find('.mce-top-part').hide();
      $editingBlock.find('.full-edit').addClass('block-edit-smaller-font').text('Full');
      $editingBlock.find('.block-edit-close').addClass('block-edit-smaller-font').text('Cancel');
      $editingBlock.find('.block-edit-trigger').addClass('block-edit-smaller-font').text('Save');
    }
  };

  const insertErrorModal = () => {
    if (!document.getElementById('errorModal')) {
      const modalHTML = `
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h1 class="modal-title fs-5" id="errorModalLabel">Notice</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                You're already editing a block. Please close the current block editor before opening another.
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      `;
      document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
  };

  // function to handle inline editing
  function handleInlineEditing(postId, editType = 'block') {
    const blockSelector = editType === 'block' ? `[data-block-content='${postId}']` : `[data-block-title='${postId}']`;
    const blockElement = $(blockSelector);
    const originalContent = blockElement.html();

    // store the original content in a data attribute
    blockElement.data('original-content', originalContent);

    // check if any element in the document has the 'editing' class
    if ($('.editing').length > 0) {
      insertErrorModal();
      // show an error modal using Bootstrap's modal functionality
      const errorModal = new bootstrap.Modal(document.getElementById('errorModal'), {
        keyboard: true,
        backdrop: 'static',
        focus: true
      });
      errorModal.show();
      
      console.log('Another element is already being edited.');
      return; 
    }

    // fetch and display the editing ui
    $.ajax({
      url: customBlocks.ajaxurl,
      type: 'GET',
      data: {
        action: 'edit_block_form',
        edit_type: editType,
        post_id: postId
      },
      success: function (response) {
        blockElement.addClass('editing').html(response).trigger('classChange');
        blockElement.attr('data-edit-type', editType);
        adjustInlinePosition(blockElement); // adjust based on the width of the block
        
        // set the border color of the editable content to match the text color
        const editedTitle = blockElement.find('.editable-content');
        let editedBlockColor = blockElement.find('label').css('color');
        const editedTitleColor = editedTitle.css('color');

        if (editedBlockColor === 'rgb(0, 0, 0)') {
          editedBlockColor = 'rgb(134, 134, 134)';
        }

        editedTitle.css('border-color', editedTitleColor);
        blockElement.css('border-color', editedBlockColor);

        if (editType === 'block') {
          // initialize the TinyMCE editor with specific configurations
          wp.editor.initialize('block_content', {
            tinymce: {
              wpautop: true,
              content_css: editorCssString,
              toolbar1: 'undo,redo,formatselect,bold,italic,strikethrough,blockquote,alignleft,aligncenter,alignright,link,unlink,forecolor,outdent,indent,bullist',
              menubar: false,
            },
            quicktags: {
              buttons: "strong,em,link,ul,li"
            },
          });
        }
      },
      error: function () {
        alert('Error loading edit form.');
      }
    });
  }

  // save function for the inline editor
  function saveInlineEdit(postId, editType = 'block') {
    $('.spinner-border').show();
    $('.bi-check-lg').hide();
    tinyMCE.triggerSave();
  
    const data = {
      action: 'edit_block',
      form: $('.block-edit form').serialize(),
      security: customBlocks.nonce,
    };
  
    $.post(customBlocks.ajaxurl, data, (response) => {
      const responseData = JSON.parse(response);
      $('.spinner-border').hide();
      $('.bi-check-lg').show();
      $(`[data-block-title='${responseData.post_id}']`).html(responseData.block_title);
      $(`[data-block-content='${responseData.post_id}']`).html(responseData.block_content);
    }).fail((jqXHR, textStatus, errorThrown) => {
      console.error("Error: ", textStatus, errorThrown);
      $('.spinner-border').hide();
      $('.bi-check-lg').show();
    });
  }

  // function to handle closing the inline editor
  function closeInlineEditor(postId, editType = 'block') {
    const blockSelector = editType === 'block' ? `[data-block-content='${postId}']` : `[data-block-title='${postId}']`;
    const blockElement = $(blockSelector);
    const originalContent = blockElement.data('original-content');
    
    blockElement.removeClass('editing').html(originalContent).trigger('classChange');
    
    block_edit_close_modal();
  }

  // attach event listener to edit button
  $(document).on('click', '.edit_block', function(e) {
    e.preventDefault();
    var postId = $(this).data('post-id');
    var editType = $(this).data('edit-type');
    handleInlineEditing(postId, editType);
  });

  // event listener for save button inside the block
  $(document).on('click', '.save-block', function() {
    const postId = $(this).closest('[data-block-content]').data('post-id');
    const editType = $(this).closest('[data-block-content]').data('edit-type');
    saveInlineEdit(postId, editType);
  });


  // update hidden input on content change
  $(document).on('input', '#block_title_editable', function() {
    $('#block_title').val($(this).text());
  });

  // ensure the edit block is in the right place by updating parent position
  if ($('.edit_block').length > 0) {
    $('.edit_block').each(function() {
      const $parent = $(this).parent();
      
      // check if the immediate parent is a <p> tag
      if ($parent.is('p')) {
        // apply the style to the grandparent
        $parent.parent().addClass('is-editable');
      } else {
        // apply the style to the parent
        $parent.addClass('is-editable');
      }
    });
  }

});