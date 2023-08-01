<?php

declare(strict_types = 1);

namespace Drupal\block_task\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block task block.
 *
 * @Block(
 *   id = "Custom_block",
 *   admin_label = @Translation(" custom block "),
 *   category = @Translation("Custom"),
 * )
 */
final class CustomBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new BlockTaskBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity storage manager. Corrected parameter name.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entityTypeManager, EntityDisplayRepositoryInterface $entityDisplayRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    // Specifies the ID of the node that the
    // block should render. The default value is an empty string.
    return [
      'entity_field' => '',
      'display_mode' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    // Add the reference field for nodes with autocomplete.
    $form['entity_field'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Entity Field'),
      '#target_type' => 'node',
    ];
    $display_modes = $this->getDisplayModes();

    // Add the view mode options to the form.
    $form['display_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display Mode'),
      '#options' => $display_modes,
      '#default_value' => $this->configuration['display_mode'],
    ];

    /* If the entity_field configuration setting is not empty, set the default value of the form element to the node with the ID specified in the configuration setting. */
    if (!empty($this->configuration['entity_field'])) {
      $form['entity_field']['#default_value'] = Node::load($this->configuration['entity_field']);

    }

    return $form;
  }

  /**
   * Helper function to get available view modes for an entity type.
   *
   * @return array
   *   An array of available view modes.
   */
  protected function getDisplayModes() {
    // Initialize an empty array to store the display modes.
    $display_modes = [];
    // Get all of the view modes for the `node` entity type.
    $view_modes = $this->entityDisplayRepository->getViewModes('node');
    // Iterate over the view modes and add them to the `$display_modes` array.
    foreach ($view_modes as $view_mode => $info) {
      $display_modes[$view_mode] = $info['label'];
    }

    return $display_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    // Get the value of the entity_field form element.
    $entity_field = $form_state->getValue('entity_field');

    // Set entity_field configuration setting to the value of the form element.
    $this->configuration['entity_field'] = $entity_field;
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Get the node ID from the configuration.
    $node_id = $this->configuration['entity_field'];
    $view_mode = $this->configuration['display_mode'];
    // Load the node by ID.
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    // Create an array with the node's label.
    $build = [];
    if ($node) {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $build = $view_builder->view($node, $view_mode);
    }
    // Return the array.
    return $build;
  }

}
