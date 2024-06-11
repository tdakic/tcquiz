<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions related to quizacess_tcquiz.
 *
 * @package   quizaccess_tcquiz
 * @category  test
 * @copyright 2024 Tamara Dakic @ Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../../../question/tests/behat/behat_question_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

use Behat\Mink\Exception\ExpectationException as ExpectationException;

use Behat\Mink\Exception\ResponseTextException;

use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

/**
 * Steps definitions related to quizacess_tcquiz.
 *
 * @copyright 2024 Tamara Dakic
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_quizaccess_tcquiz extends behat_base {


    /**
     * Waits for a given number of seconds.
     *
     * @param int $seconds
     *   How long to wait.
     *
     * @When I wait :seconds second(s)
     */
    public function wait($seconds) {
        sleep($seconds);
    }

    /**
     * Wait at most $seconds seconds for the $text to appear
     *
     * @When I wait :seconds second(s) for :text to appear
     *
     * @param int $seconds
     * @param int $text
     * @throws ResponseTextException
     */
    public function i_wait_for_text_to_appear($seconds, $text) {
        /* couldn't figure out how to use the spin function from
           https://stackoverflow.com/questions/46737255/how-to-make-behat-wait-for-an-element-to-be-displayed-on-the-screen-before-filli
        */

        $starttime = time();

        do {
            try {
                $node = $this->getSession()->getPage()->find("named", ["content", $text]);
                if ($node) {
                    return true;
                }
            } catch (ExpectationException $e) {
                /* Intentionally left blank. */
            }
        } while (time() - $starttime < $seconds);

        throw new ResponseTextException(
            sprintf('Cannot find the element .answer after %s seconds', $seconds),
            $this->getSession()
        );

    }

    /**
     * Wait for the page to be loaded - not used
     *
     * @When /^I wait for the page to be loaded$/
     */
    public function i_wait_for_the_page_to_be_loaded() {
        $xml = file_get_contents($this->getSession()->getCurrentUrl());
        $this->getSession()->wait(10000, "document.readyState === 'complete'");
    }


}
