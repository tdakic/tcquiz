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
 * Timer used as a web worker in both teachercontrols.js and studenttimer.js
 * in order to try to syncronize the end time of a question.
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

onmessage = function (event) {

  var secondsleft = event.data.timeForQuestion;

  var timer = setInterval(function() {

          postMessage(secondsleft);
          if (secondsleft <= 0) {
            clearInterval(timer);
            timer = null;
          }
          secondsleft--;
      }, 1000);


};
