<?php

namespace Drupal\example_customD8_migrate_beta\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Apply parent taxonomy term id for topic.
 *
 * @MigrateProcessPlugin(
 *   id = "beta_topics"
 * )
 */
class BetaTopics extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $topic = NULL;
    // Get the mapped parent taxonomy term id.
    $terms = $this->getTopics();
    $trimmed_value = trim($value);
    if (array_key_exists($trimmed_value, $terms)) {
      $topic = $terms[$trimmed_value];
    }
    return $topic;
  }

  /**
   * Get array of mapped topics.
   *
   * @return array
   *   An array of terms and tids.
   */
  protected function getTopics() {
    $topics = [
      'Board & Committee Charters' => 232,
      'Board Composition & Selection' => 539,
      'Board Culture' => 540,
      'Board Development' => 541,
      'Board Evaluation' => 538,
      'Board Infrastructure' => 542,
      'Board Meetings' => 543,
      'Board Orientation' => 544,
      'Board Structure' => 545,
      'Checklists' => 235,
      'Community Benefit' => 603,
      'Current & Emerging Payment Models' => 406,
      'Cybersecurity' => 434,
      'Dashboards/Scorecards' => 236,
      'Environmental Trends' => 686,
      'Equity of Care' => 443,
      'Evaluations & Assessments' => 238,
      'Executive Performance & Compensation' => 546,
      'Fiduciary Duties' => 547,
      'Financial Oversight' => 548,
      'Foundation Board Resources' => 549,
      'Health Information Technology (HIT)' => 437,
      'Hospitals Against Violence' => 573,
      'Innovation' => 669,
      'MACRA' => 420,
      'Mentor Feedback Form' => 239,
      'Mission/Vision/Strategy' => 550,
      'New Delivery Models' => 406,
      'Performance improvement' => 533,
      'PFS/MACRA/QPP' => 420,
      'Physician Issues' => 551,
      'Population/Community Health' => 504,
      'Position Descriptions' => 240,
      'Quality Oversight' => 552,
      'Sample Agendas' => 241,
      'System Board Resources' => 553,
      'System Resources' => 553,
      'Telling the Hospital Story' => 556,
      'Transforming Governance' => 554,
      'Workforce' => 468,
    ];
    return $topics;
  }

}
