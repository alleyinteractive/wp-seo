/**
 * Functionality related to the WP SEO admin controls.
 */

const init = () => {
  setUpRepeatableGroups();
  setUpCharacterCountFields();
}

/**
 * Create an "Add another repeatable group" button.
 *
 * @return {HTMLButtonElement}
 */
const wpSeoAddMoreButton = () => {
  const addMoreButton = document.createElement('button');
  addMoreButton.setAttribute('type', 'button');
  addMoreButton.classList.add('button-secondary', 'wp-seo-add');
  addMoreButton.textContent = wp_seo_admin.repeatable_add_more_label;
  return addMoreButton;
}

/**
 * Toggle the display of the "Remove group" links for a group of nodes.
 *
 * @param  {Object} $parent The .node parent
 */
const wpSeoToggleRemoves = (parent) => {
  const deleteButton = parent.querySelector('.wp-seo-delete');

  if (parent.children.length > 1) {
    deleteButton.style.display = 'block';
  } else {
    deleteButton.style.display = 'none';
  }
}

/**
 * Update the description and title character counts displayed to the user.
 */
const wpSeoUpdateCharacterCounts = () => {
  wp_seo_admin.character_count_fields.forEach((field) => {
    var input = document.querySelector(`#wp_seo_meta_${field}`);

    if (input.value.length >= 0) {
      const countElement = document.querySelector(`.${field}-character-count`);
      countElement.innerHTML = input.value.length;
    }
  });
}

/**
 * Handles clicks on add and remove buttons for each repeatable group.
 *
 * @param {event} Event button event.
 */
const handleButtonClick = (event) => {
  event.preventDefault();

  const buttonsOnly = event.target.closest("button");
  if (buttonsOnly === null) return;

  if (event.target.classList.contains('wp-seo-add')) {
    // Add a repeatable group on click.
    const template = event.target.previousElementSibling;
    const html = _.template(event.target.previousElementSibling.innerHTML);
    const templateStart = template.getAttribute('data-start');

    template.previousElementSibling.insertAdjacentHTML('beforeend', html({ i: templateStart }));

    template.setAttribute('data-start', parseInt(templateStart) + 1);
    wpSeoToggleRemoves(template.previousElementSibling);
  } else {
    // Remove a repeatable group on click.
    const parentElement = event.target.parentNode;

    parentElement.remove();
    wpSeoToggleRemoves(parentElement);
  }
};

/**
 * Add character count to fields where it is enabled.
 */
const setUpCharacterCountFields = () => {
  const characterCountFields = document.querySelectorAll('.wp-seo-post-meta-fields input, .wp-seo-post-meta-fields textarea, .wp-seo-term-meta-fields input, .wp-seo-term-meta-fields textarea');

  if (characterCountFields.length) {
    wpSeoUpdateCharacterCounts();
  }

  characterCountFields?.forEach((field) => {
    field.addEventListener('keyup', wpSeoUpdateCharacterCounts);
  });

  // Update the character counts after a term is added via AJAX.
  jQuery(document).ajaxComplete(function () {
    if (jQuery('#addtag').length > 0) {
      wpSeoUpdateCharacterCounts();
    }
  });
}

/**
 * Add controls to repeatable groups.
 */
const setUpRepeatableGroups = () => {
  const repeatableGroups = document.querySelectorAll('.wp-seo-repeatable-group');
  const repeatableFields = document.querySelectorAll('.wp-seo-repeatable');

  /**
 * Add a "Remove" button to each field group.
 */
  repeatableGroups?.forEach((group) => {
    const deleteButton = document.createElement('button');
    deleteButton.setAttribute('type', 'button');
    deleteButton.classList.add('wp-seo-delete');
    deleteButton.textContent = wp_seo_admin.repeatable_remove_label;

    group.appendChild(deleteButton);
  });

  /**
   * Add an "Add" button to each repeatable group.
   */
  repeatableFields?.forEach((repeatable) => {
    repeatable.appendChild(wpSeoAddMoreButton());
    const repeatableContainer = repeatable.querySelector('.nodes');
    wpSeoToggleRemoves(repeatableContainer);

    repeatable.addEventListener('click', handleButtonClick);
  });
}

init();
