<?php

namespace Drupal\dashboards\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\EntityPermissionsForm;

/**
 * Add form for get correct route parameter.
 *
 * @package Drupal\dashboards\Form
 */
class DashboardPermissionForm extends EntityPermissionsForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $bundle_entity_type = NULL, $bundle = NULL): array {
    $dashboard = \Drupal::routeMatch()->getParameter("dashboard");
    return parent::buildForm($form, $form_state, $bundle_entity_type, $dashboard);
  }

}
