<?php

namespace Drupal\commerce_pos;

use Drupal\commerce_pos\Form\POSForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\commerce_order\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Provides main POS page.
 */
class POS extends ControllerBase {

  /**
   * The container object.
   *
   * @var Symfony\Component\DependencyInjection\ContainerInterface;
   */
  protected $container;

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The currentOrder object.
   *
   * @var \Drupal\commerce_pos\CurrentOrder
   */
  protected $currentOrder;

  /**
   * Constructs a new POS object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(ContainerInterface $container, PrivateTempStoreFactory $temp_store_factory, CurrentOrder $current_order) {
    $this->container = $container;
    $this->tempStore = $temp_store_factory->get('commerce_pos');
    $this->currentOrder = $current_order;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('user.private_tempstore'),
      $container->get('commerce_pos.current_order')
    );
  }

  /**
   * Builds the POS form.
   *
   * @return array
   *   A renderable array containing the POS form.
   */
  public function posForm() {
    $register = $this->tempStore->get('register');

    if (empty($register) || !($register = \Drupal::entityTypeManager()->getStorage('commerce_pos_register')->load($register))) {
      return \Drupal::formBuilder()->getForm('\Drupal\commerce_pos\Form\RegisterSelectForm');
    }

    $store = $register->getStoreId();

    $order = $this->currentOrder->get();

    if (!$order) {
      $order = Order::create([
        'type' => 'pos',
        'field_cashier' => \Drupal::currentUser()->id(),
      ]);

      $order->setStoreId($store);

      $order->save();

      $this->currentOrder->set($order);
    }

    $form_object = POSForm::create($this->container);
    $form_object->setEntity($order);

    $form_object
      ->setModuleHandler(\Drupal::moduleHandler())
      ->setEntityTypeManager(\Drupal::entityTypeManager())
      ->setOperation('pos')
      ->setEntityManager(\Drupal::entityManager());

    $form_state = (new FormState())->setFormState([]);

    return \Drupal::formBuilder()->buildForm($form_object, $form_state);
  }

}
