<div class="block-edit block-edit-wrapper" data-post-id="<?php echo $post->ID ?>" data-edit-type="<?php echo $edit_type ?>" role="dialog" aria-labelledby="editBlockHeader" aria-modal="true">  
  <div class="block-edit-body">
    <form>
      <input type="hidden" name="post_id" value="<?php echo $post->ID ?>">

<?php if ( $edit_type == 'title' ): ?>

        <div>
          <label for="block_title" class="visually-hidden">Block Title</label>
          <div contenteditable="true" id="block_title_editable" class="editable-content" aria-label="Block Title"><?php echo esc_attr($block_title) ?></div>
        </div>
        <input type="hidden" id="block_title" name="block_title" value="<?php echo esc_attr($block_title) ?>">
        <input type="hidden" id="block_content" name="block_content" value="<?php echo htmlspecialchars($post->post_content, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" id="block_href" name="block_href" value="<?php echo esc_attr($block_href) ?>">
        <input type="hidden" id="block_image_id" name="block_image_id" value="<?php echo $block_thumbnail_id ?>">

<?php else: ?>

        <input type="hidden" id="block_title" name="block_title" value="<?php echo esc_attr($block_title) ?>">
      <div class="block-pane">
        <textarea id="block_content" name="block_content" class="wp-editor"><?php echo htmlspecialchars($post->post_content, ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>
      <div class="href-pane">
        <div class="form-floating">
          <input type="text" id="block_href" class="form-control" name="block_href" value="<?php echo esc_attr($block_href) ?>">
          <label for="block_href">Block HREF</label>
        </div>
      </div>
      <div class="image-pane">
        <div class="image-header">
          <label>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-image-fill" viewBox="0 0 16 16">
              <path d="M.002 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-12a2 2 0 0 1-2-2zm1 9v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062zm5-6.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/>
            </svg>
            Block Image
          </label>
          <small>Select an image to display with this block.</small>
        </div>
        <div class="image-preview <?php if ($block_thumbnail_id == 0) echo "hide" ?>">
          <input id="block_image_id" name="block_image_id" type="hidden" value="<?php echo $block_thumbnail_id ?>">
          <img src="<?php echo $block_thumbnail_url ?>" alt="This contains the preview for the image block." id="block_image_preview" data-default-src="<?php echo $default_src ?>">
        </div>
        <div class="image-controls">
          <div>
            <button type="button" id="block_image_trigger" class="button">Choose Image</button>
            <button type="button" id="block_image_remove" class="button <?php if ($block_thumbnail_id == 0) echo "hide" ?>">Remove Image</button>
          </div>
        </div>
      </div>

<?php endif ?>

    </form>
  </div><div class="block-edit-header">
    <div>
      <?php if ( $edit_type == 'block' ): ?>
        
          <a href="<?= get_edit_post_link( $post->ID ) ?>" target="_blank" class="full-edit" rel="noopener noreferrer" aria-label="Full edit of block">Full Edit</a>
        
      <?php endif ?>
      
        <button class="block-edit-close icon" aria-label="Close block editor">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
          </svg>
          <label>Cancel</label>
        </button>
      
        <img src="/wp-includes/images/spinner.gif" alt="Loading content, please wait." class="block-edit-spinner">
        
        <button type="button" class="block-edit-trigger button icon" aria-label="Update">
          <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
            <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
          </svg>
          <label>Save</label>
        </button>
        
    </div>
  </div>
</div>

