<?php
/**
 * @file
 * Contains \Drupal\Ak_news\Plugin\Block\Ak_newsBlock
 */
 namespace Drupal\Ak_news\Plugin\Block;
 
 use Drupal\Core\Block\BlockBase;
 use Drupal\Core\Session\AccountInterface;
 use Drupal\Core\Access\AccessResult;
 use Drupal\Core\Database\Database;


 /**
 * Provides a 'NEWS' List Block
 *
 * @Block(
 *   id = "news_block",
 *   admin_label = @Translation("NEWS Block"),
 *   category = @Translation("Blocks")
 * )
 */
class Ak_newsBlock extends BlockBase {

  /**
   * Gets all RSVPs for all nodes.
   *
   * @return array
   */
  protected function load() {
    $select = Database::getConnection()->select('node_field_data', 'n');
    $select->join('node__field_news_date', 'u', 'n.uid = u.entity_id');
    $select->addField('n','nid',);
    $select->addField('n','title');
    $select->addField('u', 'field_news_date_value');
    $select->orderBy('field_news_date_value', 'DESC');
    $select->range(0, 5);
    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return $entries;
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = array();
    $rows = array();
    foreach ($entries = $this->load() as $entry) {
      $rows[] = $entry;
    }
    // $entry = load();
    $renderable = [
      '#theme' => 'my_ul_list',
      '#ns_var' => $rows,
    ];

    return $renderable;
  }

}

