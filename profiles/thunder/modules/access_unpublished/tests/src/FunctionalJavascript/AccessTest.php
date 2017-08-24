<?php

namespace Drupal\Tests\access_unpublished\FunctionalJavascript;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\RoleInterface;

/**
 * Tests the article creation.
 *
 * @group access_unpublished
 */
class AccessTest extends JavascriptTestBase {

  protected $profile = 'standard';

  public static $modules = ['access_unpublished'];

  /**
   * Checks node access before and after token creation.
   */
  public function testAccessAllowed() {

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access content',
      'access_unpublished node article',
    ]);

    $node = Node::create([
      'title' => 'Foo',
      'type' => 'article',
      'status' => 0,
    ]);
    $node->save();

    $this->drupalGet($node->url());

    $this->assertSession()->statusCodeEquals(403);

    AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $node->id(),
      'value' => '12345',
      'expire' => REQUEST_TIME + 100,
    ])->save();

    $this->drupalGet($node->url('canonical'), ['query' => ['auHash' => 12345]]);
    $this->assertSession()->statusCodeEquals(200);
  }

}
