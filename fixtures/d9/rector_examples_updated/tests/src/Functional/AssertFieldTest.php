<?php

namespace Drupal\Tests\rector_examples\Functional;

use Drupal\Tests\BrowserTestBase;

class AssertFieldTest extends BrowserTestBase {

    public function testExample() {
        // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
        // Change assertion to buttonExists() if checking for a button.
        $this->assertSession()->fieldExists('files[upload]', 'Found file upload field.');
    }

}
