<?php

namespace Drupal\tab_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form for management of tabs (local task links) on a current page.
 */
class TabManagerSettingsForm extends ConfigFormBase {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tab_manager_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tab_manager.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_tabs = $this->state->get('tab_manager_current_tabs');

    if (empty($current_tabs)) {
      $form['message'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('There are no tabs on this page.'),
      ];
      return $form;
    }

    foreach ($current_tabs as $level => $tabs) {
      // Sorting the array by 'weight' so that the rows in tables are sorted.
      uasort($tabs, function ($a, $b) {
        return $a['weight'] <=> $b['weight'];
      });

      $form[$level] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        // Add 1 to start from 'Level: 1' for better user interface.
        '#value' => $this->t('Level: @level', ['@level' => ($level + 1)]),
      ];

      $name = 'tabs_level' . $level;
      $form[$name] = [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Weight'), ''],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'table-sort-weight',
          ],
        ],
      ];
      foreach ($tabs as $key => $settings) {
        $form[$name][$key]['#attributes']['class'][] = 'draggable';
        $form[$name][$key]['#weight'] = $settings['weight'];
        $form[$name][$key]['title'] = [
          '#type' => 'textfield',
          '#default_value' => $settings['title'],
          '#size' => 20,
          '#required' => TRUE,
        ];

        $form[$name][$key]['weight'] = [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          '#default_value' => $settings['weight'],
          '#attributes' => ['class' => ['table-sort-weight']],
        ];

        $form[$name][$key]['hidden'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('hide'),
          '#default_value' => $settings['hidden'],
        ];
      }
    }

    // ------------ Butons ----------------------------------------------------.
    $form['br'] = ['#type' => 'html_tag', '#tag' => 'br'];

    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['actions']['reset_to_default'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset to Default'),
      '#button_type' => 'danger',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // In our configuration info about tabs stored in the following format:
    // [$tab_1, tab_2, ... tab_n]
    // where
    // $tab_1 = ['id' => 'id-1', 'title' => 'title-1', 'weight' => 'weight-1']
    // $tab_2 = ['id' => 'id-2', 'title' => 'title-2', 'weight' => 'weight-2']
    // (this structure is due to the fact that dots keys are not allowed in the
    // configuration).
    // Let's transform it into the same structure that was used in the
    // hook_menu_local_tasks_alter() for stiring the $current_tabs:
    // ['id-1' => $tab_1, 'id-2' => tab_2, ... 'id-n' => tab_n]
    // where
    // $tab_1 = ['title' => 'title-1', 'weight' => 'weight-1']
    // $tab_2 = ['title' => 'title-2', 'weight' => 'weight-2'].
    $modified_tabs = [];
    $config = $this->config('tab_manager.settings');

    foreach ($config->get('tabs') as $tab) {
      $modified_tabs[$tab['id']] = [
        'title' => $tab['title'],
        'weight' => $tab['weight'],
        'hidden' => $tab['hidden'],
      ];
    }

    if ($form_state->getTriggeringElement()['#id'] == 'edit-save') {
      $this->save($form_state, $modified_tabs);
    }
    else {
      $this->resetToDefault($modified_tabs);
    }

    // Redirect to the page with tabs to force reloading and to see changes.
    $tabs_page_url = $this->state->get('tab_manager_tabs_page_url');
    $form_state->setRedirectUrl($tabs_page_url);
  }

  /**
   * Save settings of tags.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Array with the settings of the tabs.
   * @param array $modified_tabs
   *   Array with tabs settings.
   */
  private function save(FormStateInterface $form_state, array $modified_tabs) {
    $current_tabs = $this->state->get('tab_manager_current_tabs');
    $levels_number = count($current_tabs);

    for ($i = 0; $i < $levels_number; $i++) {
      $tabs = $form_state->getValue('tabs_level' . $i);
      $modified_tabs = array_merge($modified_tabs, $tabs);
    }

    $this->updateConfiguration($modified_tabs);
  }

  /**
   * Reset tabs settings to default.
   *
   * @param array $modified_tabs
   *   Array with tabs settings.
   */
  private function resetToDefault(array $modified_tabs) {
    $current_tabs = $this->state->get('tab_manager_current_tabs');

    foreach ($current_tabs as $tabs) {
      $modified_tabs = array_diff_key($modified_tabs, $tabs);
    }
    $this->updateConfiguration($modified_tabs);
  }

  /**
   * Store the updated data about tab in the configuration.
   *
   * Before saving of updated configuration convert the data to the following
   * format:
   * [$tab_1, tab_2, ... tab_n]
   * where
   * $tab_1 = ['id' => 'id-1', 'title' => 'title-1', 'weight' => 'weight-1']
   * $tab_2 = ['id' => 'id-2', 'title' => 'title-2', 'weight' => 'weight-2']
   * (We need this because 'id' keys can have dots, but keys with dots are
   * not allowed in the configuration).
   *
   * @param array $modified_tabs
   *   Array with tabs settings.
   */
  private function updateConfiguration(array $modified_tabs) {
    // $modified_tabs has the following strucute:
    // ['id-1' => $tab_1, 'id-2' => tab_2, ... 'id-n' => tab_n]
    // where
    // $tab_1 = ['title' => 'title-1', 'weight' => 'weight-1']
    // $tab_2 = ['title' => 'title-2', 'weight' => 'weight-2'].
    $tabs = [];

    foreach ($modified_tabs as $id => $tab) {
      $tabs[] = [
        'id' => $id,
        'title' => $tab['title'],
        'weight' => $tab['weight'],
        'hidden' => $tab['hidden'],
      ];
    }
    // Save configuration.
    $settings = $this->configFactory->getEditable('tab_manager.settings');
    $settings->set('tabs', $tabs)->save();
  }

}
