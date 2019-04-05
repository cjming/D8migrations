<?php

namespace Drupal\example_wordpressD8_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for the posts.
 *
 * @MigrateSource(
 *   id = "posts"
 * )
 */
class Posts extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select published posts.
    $query = $this->select('wp_posts', 'p');
    $query->fields('p', array_keys($this->postFields()));
    $query->condition('p.post_status', 'publish', '=');
    $query->condition('p.post_type', 'post', '=');
    return $query;
  }

  /**
   * Returns the Posts fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function postFields() {
    $fields = array(
      'id' => $this->t('Post ID'),
      'post_title' => $this->t('Title'),
      'post_content' => $this->t('Content'),
      'post_author' => $this->t('Authored by (uid)'),
      'post_type' => $this->t('Post type'),
      'post_modified' => $this->t('Post modified'),
      'post_date' => $this->t('Post date'),
      'post_name' => $this->t('Post alias'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->postFields();
    $fields['post_attachment'] = $this->t('Post Attachment');
    $fields['author'] = $this->t('Author');
    $fields['post_metatags'] = $this->t('Post metatags');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Set tag terms.
    $query = $this->select('wp_term_relationships', 'tr');
    $query->fields('tr', ['term_taxonomy_id']);
    $query->join('wp_term_taxonomy', 'tt', 'tr.term_taxonomy_id = tt.term_id');
    $query->condition('tr.object_id', $row->getSourceProperty('id'));
    $query->condition('tt.taxonomy', Term::VOCABULARIES, 'IN');
    $query->orderBy('term_order');
    $row->setSourceProperty('tags', $query->execute()->fetchCol());
    // Store the post attachment ID.
    $query = $this->select('wp_posts', 'p');
    $query->fields('p', ['id']);
    $query->condition('p.post_parent', $row->getSourceProperty('id'));
    $query->orderBy('p.id');
    $query->range(0, 1);
    $row->setSourceProperty('post_attachment', current($query->execute()->fetchCol()));
    // Set the author text.
    $this->setAuthor($row);
    // Set metatags.
    $this->setMetatags($row);
    return parent::prepareRow($row);
  }

  /**
   * Sets the author text if it is needed.
   *
   * @param \Drupal\migrate\Row $row
   *   The current content row.
   */
  protected function setAuthor(Row $row) {
    // If author user imported, do not set an author.
    $query = $this->select('wp_users', 'u');
    $query->fields('u', ['user_email']);
    $query->condition('u.id', $row->getSourceProperty('id'));
    $email = $query->execute()->fetchCol();
    if ($this->userMigrated(current($email))) {
      return;
    }

    // If author user not imported, load the author term and map it to a clean
    // author name if a match can be found.
    $query = $this->select('wp_term_taxonomy', 'tt');
    $query->fields('tt', ['description']);
    $query->join('wp_term_relationships', 'tr', 'tr.term_taxonomy_id = tt.term_id');
    $query->condition('tt.taxonomy', 'author');
    $query->condition('tr.object_id', $row->getSourceProperty('id'));
    if (!empty($author_term = $query->execute()->fetchCol())) {
      $row->setSourceProperty('author', $this->cleanAuthor(current($author_term)));
    }
  }

  /**
   * Sets metatag title and description.
   *
   * @param \Drupal\migrate\Row $row
   *   The current content row.
   */
  protected function setMetatags(Row $row) {
    // If yoast seo title exists, use it to populate meta tag title.
    $query = $this->select('wp_postmeta', 'pm');
    $query->fields('pm', ['meta_value']);
    $query->condition('pm.meta_key', '_yoast_wpseo_title');
    $query->condition('pm.post_id', $row->getSourceProperty('id'));
    // Set meta tag title from WP seo field or post title.
    $title = !empty($seo_title = $query->execute()->fetchCol()) ? $seo_title[0] : $row->getSourceProperty('post_title');
    // If yoast seo description exists, use it to populate meta tag description.
    $query = $this->select('wp_postmeta', 'pm');
    $query->fields('pm', ['meta_value']);
    $query->condition('pm.meta_key', '_yoast_wpseo_metadesc');
    $query->condition('pm.post_id', $row->getSourceProperty('id'));
    // Set meta tag description from WP seo field or post content.
    $description = !empty($seo_description = $query->execute()->fetchCol()) ? $seo_description[0] : $row->getSourceProperty('post_content');
    // Set meta tags for title and description.
    $metatags = [
      'title' => $title,
      'description' => $description,
    ];
    $row->setSourceProperty('post_metatags', serialize($metatags));
  }

  /**
   * Checks if a user was migrated by email address.
   *
   * @param string $email
   *   A user's email address.
   *
   * @return bool
   *   A boolean indicating if a user was migrated.
   */
  protected function userMigrated($email) {
    if (in_array($email, $this->usersMigrated())) {
      return TRUE;
    }
  }

  /**
   * An array of users migrated.
   *
   * @return array
   *   An array of users to import.
   */
  protected function usersMigrated() {
    return [
      'bitbucket@wpengine.com',
      'noelc@allrecipes.com',
      'editorial@allrecipes.com',
      'vanessag@allrecipes.com',
      'carlh@allrecipes.com',
      'kevin@allrecipes.com',
      'justinl@allrecipes.com',
      'allis@allrecipes.com',
      'richardk@allrecipes.com',
      'angiema@allrecipes.com',
      'vickym@allrecipes.com',
      'mirandab@allrecipes.com',
      'lindseyo@allrecipes.com',
      'merrittb@allrecipes.com',
      '15889215@dish.allrecipes.com',
      '14105758@dish.allrecipes.com',
      'lesliek@allrecipes.com',
      '15968852@dish.allrecipes.com',
      'oliviah@allrecipes.com',
      'elizabethm@allrecipes.com',
      'lorraineg@allrecipes.com',
      'jeffc@allrecipes.com',
      'heatherc@allrecipes.com',
      'julianb@allrecipes.com',
      'MarkG@allrecipes.com',
      '16659563@dish.allrecipes.com',
      '16659602@dish.allrecipes.com',
      'tracym@allrecipes.com',
      'katiej@allrecipes.com',
      '3987300@dish.allrecipes.com',
      'tyrels@allrecipes.com',
      'KristenR@Allrecipes.com',
      'judithd@allrecipes.com',
      'ErinC@allrecipes.com',
      'esmee@allrecipes.com',
      'rich.hamack@meredith.com',
      'bhavnaD@allrecipes.com',
      'beverlyr@allrecipes.com',
      'evand@allrecipes.com',
      'ms.amyp@gmail.com',
      'juliakwayne@gmail.com',
      'hannaraskin@gmail.com',
      'adrianmiller@adrianmiller.com',
      '123@123.com',
      '123@246.com',
      'megans@allrecipes.com',
      'rachelhartrios@gmail.com',
      'annaberman@123.com',
      'juliekendrick@123.com',
      'David.Michael@meredith.com',
      'nicolespiridakis@123.com',
      'melissakravitz@123.com',
      'jackiefreeman@123.com',
      'heatherlalley@123.com',
      'laurakiniry@123.com',
      'rebekahdenn@123.com',
      'amystephenson@123.com',
      'christopher.hassiotis@123.com',
    ];
  }

  /**
   * Cleans a author taxonomy term title if a match is found.
   *
   * @param string $author
   *   The author term.
   *
   * @return string|NULL
   *   The clean author name if a match is found.
   */
  protected function cleanAuthor($author) {
    $authors = [
      'Bob Smith   Bob Smith 3' => 'Bob Smith',
      'Kris Erickson Kris Erickson Editor_Kris 6 krise@allrecipes.com' => 'Kris Erickson',
      'Jen Harwell Jen Harwell Editor_Jen 10 jenniferh@allrecipes.com' => 'Jen Harwell',
      'Food*Dude K E Editor_Kris 6 krise@allrecipes.com' => 'Food*Dude',
      'Alli Shircliff Alli Shircliff Alli_Shircliff 19 allis@allrecipes.com' => 'Alli Shircliff',
      'Seth Kolloen Seth Kolloen allrecipesblog 2 sethk@allrecipes.com' => 'Seth Kolloen',
      'Allrecipes Staff   Editor_Seth 7 editorial@allrecipes.com' => 'Allrecipes Staff',
      'Noel Christmas Noel Christmas Noel 5 noelc@allrecipes.com' => 'Noel Christmas',
      'Vanessa Greaves Vanessa Greaves Editor_Vanessa 8 vanessag@allrecipes.com' => 'Vanessa Greaves',
      'Vicky McDonald Vicky McDonald Vicky_McDonald 26 vickym@allrecipes.com' => 'Vicky McDonald',
      'Karen Gaudette Brewer Karen Gaudette Editor_Karen 9 kareng@allrecipes.com' => 'Karen Gaudette Brewer',
      'Allrecipes Magazine Cheryl Brown AR_MAGAZINE 28 cheryl.brown@meredith.com' => 'Cheryl Brown',
      'Lindsey Otta Lindsey Otta Lindsey_Otta 29 lindseyo@allrecipes.com' => 'Lindsey Otta',
      'Kate Yeager Kate Yeager Kate_Yeager 31 kate.yeager0@hotmail.com' => 'Kate Yeager',
      'Leslie Kelly Leslie Kelly leslie kelly new 68344 lesliek@allrecipes.com' => 'Leslie Kelly',
      'Leslie Kelly Leslie Kelly leslie kelly new 68344 lesliek@allrecipes.com' => 'Leslie Kelly',
      'Lorraine Goldberg Lorraine Goldberg Lorraine_SOCIAL 86500 lorraineg@allrecipes.com' => 'Lorraine Goldberg',
      'Alli Shircliff Alli Shircliff Alli_Shircliff 19 allis@allrecipes.com' => 'Alli Shircliff',
      'Allrecipes Magazine Cheryl Brown AR_MAGAZINE 28 cheryl.brown@meredith.com' => 'Cheryl Brown',
      'Kristen Russell Kristen Russell Kristen_Editorial 542509 KristenR@Allrecipes.com' => 'Kristen Russell',
      'Amy Pennington Amy Pennington Amy Pennington 625907 ms.amyp@gmail.com' => 'Amy Pennington',
      'Julia Wayne Julia Wayne Julia Wayne 625908 juliakwayne@gmail.com' => 'Julia Wayne',
      'Hanna Raskin Hanna Raskin Hanna Raskin 626204 hannaraskin@gmail.com' => 'Hanna Raskin',
      'Chelsea Lin Chelsea Lin Chelsea Lin 650579 chelsea.d.lin@gmail.com' => 'Chelsea Lin',
      'Katie Johnson Katie Johnson 16971678 370490 katiej@allrecipes.com' => 'Katie Johnson',
      'Adrian Miller Adrian Miller Adrian Miller 672110 adrianmiller@adrianmiller.com' => 'Adrian Miller',
      'Jenny Cunningham Jenny Cunningham Jenny Cunningham 684899 jennyc@gmail.com' => 'Jenny Cunningham',
      'Hanna Raskin Hanna Raskin sw-54492 2311811 sw-54492@skyword.com' => 'Hanna Raskin',
      'Mackenzie Schieck Mackenzie Schieck Mackenzie Shieck 705801 123@123.com' => 'Mackenzie Schieck',
      'ARCally   16659563 263528 16659563@dish.allrecipes.com' => 'ARCally',
      'Jessica Yadegaran Jessica Yadegaran Jessica Yadegaran 874112 usethisone@123.com' => 'Jessica Yadegaran',
      'Anna Berman Anna Berman Anna Berman 1014447 annaberman@123.com' => 'Anna Berman',
      'Julie Kendrick Julie Kendrick Julie Kendrick 1014472 juliekendrick@123.com' => 'Julie Kendrick',
      'Margarita Gokun Silver Margarita Gokun Silver Margarita Gokun Silver 1014477 margaritagokunsilver@123.com' => 'Margarita Gokun Silver',
      'Nicole Spiridakis Nicole Spiridakis Nicole Spiridakis 1014492 nicolespiridakis@123.com' => 'Nicole Spiridakis',
      'Heather Lalley Heather Lalley Heather Lalley 1014768 heatherlalley@123.com' => 'Heather Lalley',
      'Jackie Freeman Jackie Freeman Jackie Freeman 1014766 jackiefreeman@123.com' => 'Jackie Freeman',
      'Jill Lightner Jill Lightner Jill Lightner 1014916 jilllightner@123.com' => 'Jill Lightner',
      'Rebekah Denn Rebekah Denn Rebekah Denn 1015022 rebekahdenn@123.com' => 'Rebekah Denn',
      'Amy Stephenson Amy Stephenson Amy Stephenson 1015157 amystephenson@123.com' => 'Amy Stephenson',
      'Karen Gaudette Karen Gaudette Editor_Karen 9 kareng@allrecipes.com' => 'Karen Gaudette',
      'Karen Gaudette   11845549 65275 11845549@dish.allrecipes.com' => 'Karen Gaudette',
      'Ishea Brown Ishea Brown Ishea_Brown 20 isheab@allrecipes.com' => 'Ishea Brown',
      'Tyrel Stendahl Tyrel Stendahl Tyrel 22 tyrels@allrecipes.com' => 'Tyrel Stendahl',
      'Miranda Benson Miranda Benson Miranda_CTAX 27 mirandab@allrecipes.com' => 'Miranda Benson',
      'Angie Ma Angie Ma Angie_CTAX 24 angiema@allrecipes.com' => 'Angie Ma',
      'Allrecipes Magazine   16007298 86124 16007298@dish.allrecipes.com' => 'Allrecipes Magazine',
      'Jeff Cummings Jeff Cummings jeff_mktg 139427 jeffc@allrecipes.com' => 'Jeff Cummings',
      'Sonja Groset Sonja Groset sonja_mktg 139430 sonjag@allrecipes.com' => 'Sonja Groset',
      'Esmee Williams Esmee Williams esmee_mktg 582796 esmee@allrecipes.com' => 'Esmee Williams',
      'Judith Dern Judith Dern Judith 556459 judithd@allrecipes.com' => 'Judith Dern',
      'Beverly Rengert Beverly Rengert Beverly Rengert 623609 beverlyr@allrecipes.com' => 'Beverly Rengert',
      'Mackenzie Schieck Mackenzie Schieck Mackenzie Shieck 705801 123@123.com' => 'Mackenzie Schieck',
      'Erin Christiansen Erin Christiansen Erin Christiansen 772268 123@246.com' => 'Erin Christiansen',
      'Julia Wayne Julia Wayne sw-54306 2459716 sw-54306@skyword.com' => 'Julia Wayne',
      'Andrea Crowley Andrea Crowley Andrea Crowley 821083 andreacrowley@123.com' => 'Andrea Crowley',
      'Lana Bandoim Lana Bandoim Lana Bandoim 1014363 lanabandoim@123.com' => 'Lana Bandoim',
      'Melissa Kravitz Melissa Kravitz Melissa Kravitz 1014745 melissakravitz@123.com' => 'Melissa Kravitz',
      'Dara Pollak Dara Pollak Dara Pollak 1014917 darapollak@123.com' => 'Dara Pollak',
      'Raghavan Iyer Raghavan Iyer Raghavan Iyer 1015057 raghavaniyer@123.com' => 'Raghavan Iyer',
      'Laura Kiniry Laura Kiniry Laura Kiniry 1014771 laurakiniry@123.com' => 'Laura Kiniry',
      'Christopher Hassiotis Christopher Hassiotis Christopher Hassiotis 1015560 christopher.hassiotis@123.com' => 'Christopher Hassiotis',
    ];
    if (!empty($authors[$author])) {
      return $authors[$author];
    }
  }

}
