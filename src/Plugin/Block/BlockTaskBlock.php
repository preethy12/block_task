<?php

declare(strict_types = 1);

namespace Drupal\block_task\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block task block.
 *
 * @Block(
 *   id = "block_task_block_task",
 *   admin_label = @Translation("block task"),
 *   category = @Translation("Custom"),
 * )
 */
final class BlockTaskBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    // Add the reference field for nodes with autocomplete.
    $form['entity_field'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Node Reference'),
      '#target_type' => 'node',
    ];
    /* If the entity_field configuration setting is not empty, set the default value of the form element to the node with the ID specified in the configuration setting. */
    if (!empty($this->configuration['entity_field'])) {
      $form['entity_field']['#default_value'] = Node::load($this->configuration['entity_field']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    // Get the value of the entity_field form element.
    $entity_field = $form_state->getValue('entity_field');

    // Set the entity_field configuration setting to the value of the form element.
    $this->configuration['entity_field'] = $entity_field;

    // Return nothing.
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Get the node ID from the configuration.
    $node_id = $this->configuration['entity_field'];
    // Load the node by ID.
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    // Create an array with the node's label.
    $build = [
      '#markup' => $node->label(),
    ];
    // Return the array.
    return $build;
  }

}
