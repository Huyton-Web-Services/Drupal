<?php

namespace Drupal\tab_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form for the "Tab Manger" module.
 *
 * The form for setting the possibility of applying changes for users with the
 * "administrator" roles. If the opion is enabled, then changes will be applied
 * to users with the "administrator" role. For example, if you hide some tab, it
 * will also not be visible to users with the "administrator" role. At the same
 * time, the fact that users have "view all local task links without changes"
 * permission will not affect the result.
 *
 * @see https://www.drupal.org/project/tab_manager/issues/3264193
 */
class TabManagerConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tab_manager_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tab_manager.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tab_manager.config');

    $form['apply_changes_for_administrator_role'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply changes for users with the "administrator" role'),
      '#description' => $this->t('If checked, then changes will be applied to users with the "administrator" role. For example, if you hide some tab, it will also not be visible to users with the "administrator" role.'),
      '#default_value' => $config->get('apply_changes_for_administrator_role'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tab_manager.config');
    $value = $form_state->getValue('apply_changes_for_administrator_role');
    $config->set('apply_changes_for_administrator_role', $value)->save();
    parent::submitForm($form, $form_state);
  }

}
