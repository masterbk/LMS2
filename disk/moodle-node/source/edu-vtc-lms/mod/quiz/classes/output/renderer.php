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

namespace mod_quiz\output;

use cm_info;
use coding_exception;
use context;
use context_module;
use html_table;
use html_table_cell;
use html_writer;
use mod_quiz\access_manager;
use mod_quiz\form\preflight_check_form;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use moodle_url;
use plugin_renderer_base;
use popup_action;
use question_display_options;
use mod_quiz\quiz_settings;
use renderable;
use single_button;
use stdClass;

/**
 * The main renderer for the quiz module.
 *
 * @package   mod_quiz
 * @category  output
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Builds the review page
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param array $slots of slots to be displayed.
     * @param int $page the current page number
     * @param bool $showall whether to show entire attempt on one page.
     * @param bool $lastpage if true the current page is the last page.
     * @param display_options $displayoptions instance of display_options.
     * @param array $summarydata contains all table data
     * @return string HTML to display.
     */
    public function review_page(quiz_attempt $attemptobj, $slots, $page, $showall,
                                             $lastpage, display_options $displayoptions, $summarydata) {

        $output = '';
        $output .= $this->header();
        //$output .= $this->review_summary_table($summarydata, $page);
        $output .= $this->review_form($page, $showall, $displayoptions,
            $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
            $attemptobj, $summarydata);
        $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Renders the review question pop-up.
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param int $slot which question to display.
     * @param int $seq which step of the question attempt to show. null = latest.
     * @param display_options $displayoptions instance of display_options.
     * @param array $summarydata contains all table data
     * @return string HTML to display.
     */
    public function review_question_page(quiz_attempt    $attemptobj, $slot, $seq,
                                         display_options $displayoptions, $summarydata) {

        $output = '';
        $output .= $this->header();
        $output .= $this->review_summary_table($summarydata, 0);

        if (!is_null($seq)) {
            $output .= $attemptobj->render_question_at_step($slot, $seq, true, $this);
        } else {
            $output .= $attemptobj->render_question($slot, true, $this);
        }

        $output .= $this->close_window_button();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Renders the review question pop-up.
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param string $message Why the review is not allowed.
     * @return string html to output.
     */
    public function review_question_not_allowed(quiz_attempt $attemptobj, $message) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(format_string($attemptobj->get_quiz_name(), true,
            ["context" => $attemptobj->get_quizobj()->get_context()]));
        $output .= $this->notification($message);
        $output .= $this->close_window_button();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Filters the summarydata array.
     *
     * @param array $summarydata contains row data for table
     * @param int $page the current page number
     * @return array updated version of the $summarydata array.
     */
    protected function filter_review_summary_table($summarydata, $page) {
        if ($page == 0) {
            return $summarydata;
        }

        // Only show some of summary table on subsequent pages.
        foreach ($summarydata as $key => $rowdata) {
            if (!in_array($key, ['user', 'attemptlist'])) {
                unset($summarydata[$key]);
            }
        }

        return $summarydata;
    }

    /**
     * Outputs the table containing data from summary data array
     *
     * @param array $summarydata contains row data for table
     * @param int $page contains the current page number
     * @return string HTML to display.
     */
    public function review_summary_table($summarydata, $page) {
        $summarydata = $this->filter_review_summary_table($summarydata, $page);
        if (empty($summarydata)) {
            return '';
        }

        $output = '';
        $output .= html_writer::start_tag('table', [
            'class' => 'generaltable generalbox quizreviewsummary']);
        $output .= html_writer::start_tag('tbody');
        foreach ($summarydata as $rowdata) {
            if ($rowdata['title'] instanceof renderable) {
                $title = $this->render($rowdata['title']);
            } else {
                $title = $rowdata['title'];
            }

            if ($rowdata['content'] instanceof renderable) {
                $content = $this->render($rowdata['content']);
            } else {
                $content = $rowdata['content'];
            }

            $output .= html_writer::tag('tr',
                html_writer::tag('th', $title, ['class' => 'cell', 'scope' => 'row']) .
                html_writer::tag('td', $content, ['class' => 'cell'])
            );
        }

        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');
        return $output;
    }

    /**
     * Renders each question
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param bool $reviewing
     * @param array $slots array of integers relating to questions
     * @param int $page current page number
     * @param bool $showall if true shows attempt on single page
     * @param display_options $displayoptions instance of display_options
     */
    public function questions(quiz_attempt    $attemptobj, $reviewing, $slots, $page, $showall,
                              display_options $displayoptions) {
        $output = '';
        foreach ($slots as $slot) {
            $output .= $attemptobj->render_question($slot, $reviewing, $this,
                $attemptobj->review_url($slot, $page, $showall));
        }
        return $output;
    }

    /**
     * Renders the main bit of the review page.
     *
     * @param int $page current page number
     * @param bool $showall if true display attempt on one page
     * @param display_options $displayoptions instance of display_options
     * @param string $content the rendered display of each question
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @return string HTML to display.
     */
    public function review_form($page, $showall, $displayoptions, $content, $attemptobj, $summarydata) {
        global $PAGE, $USER;
        $output = $PAGE->get_renderer('mod_quiz');
        $navbc = $attemptobj->get_navigation_panel($output, navigation_panel_review::class, $page, $showall);
        $navbc = $navbc->content;
        $totalQuesetion = count($attemptobj->get_slots());
        $correctAnswer = 0;
        foreach ($attemptobj->get_slots() as $slot) {
            if ($attemptobj->get_question_attempt($slot)->get_fraction() > 0) {
                $correctAnswer++;
            }

        }
        $attempts = quiz_get_user_attempts($attemptobj->get_quizid(), $USER->id, 'finished', true);
        $htmlHistoryAttempts = '';
        $quiz = $attemptobj->get_quiz();
        $quizCm = $attemptobj->get_cm();
        $currentAttempt = $attemptobj->get_attempt();
        $courseid= $attemptobj->get_courseid();

        if ($attempts) {
            $htmlHistoryAttempts = '<table class="w-100 custom-table-modal-quiz-review">
                                    <thead>
                                        <tr>
                                            <th>
                                                ' . get_string('time', 'theme_moove') . '
                                            </th>
                                            <th>
                                                ' . get_string('grade', 'theme_moove') . '
                                            </th>
                                            <th>
                                                ' . get_string('examtime', 'theme_moove') . '
                                            </th>
                                            <th>
                                                ' . get_string('isthecurrentassignment', 'theme_moove') . '
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>';
            foreach ($attempts as $attempt) {
                $htmlHistoryAttempts .= '<tr>';
                $htmlHistoryAttempts .= '<td>' . date('H:i,d/m/Y', $attempt->timestart) . '</td>';
                $htmlHistoryAttempts .= '<td>' . (int)$attempt->sumgrades . ' / ' . (int)$quiz->sumgrades . '</td>';

                $timetaken = "-";
                if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
                    if ($attempt->timefinish == 0) {
                        $timetaken = 0;
                    } else {
                        if ($quiz->timelimit && $timetaken > ($quiz->timelimit + 60)) {
                            $overtime = $timetaken - $quiz->timelimit;
                            $timetaken = format_time($overtime);
                        } else {
                            $timetaken = format_time($timetaken);
                        }
                    }

                }
                $htmlHistoryAttempts .= '<td>' . $timetaken . '</td>';
                if ($currentAttempt->id == $attempt->id) {
                    $htmlHistoryAttempts .= '<td>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="27" viewBox="0 0 28 27" fill="none">
                                                <path d="M7.81589 19.4627C7.67977 19.4627 7.54872 19.4346 7.42272 19.3783C7.29594 19.3221 7.18394 19.2471 7.08672 19.1533L2.27422 14.5127C2.09922 14.3439 2.01172 14.1422 2.01172 13.9074C2.01172 13.6734 2.09922 13.4721 2.27422 13.3033C2.42977 13.1346 2.62927 13.0502 2.87272 13.0502C3.11539 13.0502 3.32422 13.1346 3.49922 13.3033L7.84505 17.4939L8.22422 17.1002L9.47839 18.2814L8.54505 19.1533C8.44783 19.2471 8.33583 19.3221 8.20905 19.3783C8.08305 19.4346 7.952 19.4627 7.81589 19.4627ZM14.4076 19.4346C14.2714 19.4346 14.1404 19.4109 14.0144 19.3637C13.8876 19.3172 13.7756 19.2471 13.6784 19.1533L8.83672 14.4846C8.66172 14.3158 8.57422 14.1141 8.57422 13.8793C8.57422 13.6453 8.66172 13.4439 8.83672 13.2752C9.01172 13.1252 9.21589 13.0502 9.44922 13.0502C9.68255 13.0502 9.88672 13.1252 10.0617 13.2752L14.4076 17.4658L24.5284 7.70645C24.7034 7.55645 24.9076 7.48145 25.1409 7.48145C25.3742 7.48145 25.5784 7.55645 25.7534 7.70645C25.9284 7.8752 26.0159 8.07657 26.0159 8.31057C26.0159 8.54532 25.9284 8.74707 25.7534 8.91582L15.1367 19.1533C15.0395 19.2471 14.9279 19.3172 14.8019 19.3637C14.6751 19.4109 14.5437 19.4346 14.4076 19.4346ZM13.9992 13.8939L12.7451 12.7127L17.9076 7.73457C18.0826 7.56582 18.2914 7.48145 18.5341 7.48145C18.7775 7.48145 18.9867 7.56582 19.1617 7.73457C19.3367 7.90332 19.4242 8.1002 19.4242 8.3252C19.4242 8.5502 19.3367 8.74707 19.1617 8.91582L13.9992 13.8939Z" fill="#34B53A"/>
                                            </svg>
                                         </td>';
                } else {
                    $htmlHistoryAttempts .= '<td></td>';
                }


                $htmlHistoryAttempts .= '</tr>';

            }
            $htmlHistoryAttempts .= '</tbody></table>';
        }
        if ($displayoptions->flags != question_display_options::EDITABLE) {
            return $content;
        }

        $this->page->requires->js_init_call('M.mod_quiz.init_review_form', null, false,
            quiz_get_js_module());

        $output = '';
        $output .= html_writer::start_tag('form', ['action' => $attemptobj->review_url(null,
            $page, $showall), 'method'                      => 'post', 'class' => 'custom-form-quiz questionflagsaveform']);
        $output .= html_writer::start_tag('div', ['class' => 'custom-list-question']);
        $output .= $content;
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $output .= html_writer::start_tag('div', ['class' => 'submitbtns']);
        $output .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'questionflagsavebutton btn btn-secondary', 'name' => 'savingflags', 'value' => get_string('saveflags', 'question')]);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', ['class' => 'custom-list-question-minimap hidden-md']);
        $output .= "<div style='position: sticky; top: 1em'>";
        $timetaken = isset($summarydata['timetaken']['content']) ? $summarydata['timetaken']['content'] : $summarydata['state']['content'];
        $grade = isset($summarydata['timetaken']['content']) ? (int)$currentAttempt->sumgrades . '/' . (int)$quiz->sumgrades . ' (' . $currentAttempt->sumgrades * 100 / $quiz->sumgrades . '%)' : '';

        $examStatusPassed = false;
        $gradinginfo = grade_get_grades($courseid, 'mod', 'quiz', $quiz->id, $USER->id);
        $settingGrade= false;
        if(!empty($gradinginfo->items)){
            $settingGrade = $gradinginfo->items[0];
        }
        if($settingGrade != false && $settingGrade->gradepass <= $currentAttempt->sumgrades){
            $examStatusPassed = true;
        }
        if($examStatusPassed){
            $examStatusPassedText ='<td class="text-right" style="color: #28A164;font-size: 16px;font-weight: 600;">'.get_string('exampassed','theme_moove').'</td>';
        }else{
            $examStatusPassedText ='<td class="text-right" style="color: #FF0202;font-size: 16px;font-weight: 600;">'.get_string('examnotpass','theme_moove').'</td>';

        }
        $output .= '<table class="custom-quiz-review-table w-100">
                        <tbody>
                            <tr>
                                <td>' . get_string('examstatus', 'theme_moove') . '</td>
                                '.$examStatusPassedText.'
                            </tr>
                            <tr>
                                <td>' . get_string('starttime', 'theme_moove') . '</td>
                                <td class="text-right color-1890FF" >' . date('H:i,d/m/Y', $currentAttempt->timestart) . '</td>
                            </tr>
                            <tr>
                                <td>' . get_string('dotime', 'theme_moove') . '</td>
                                <td class="text-right color-1890FF">' . $timetaken . '</td>
                            </tr>
                             <tr>
                                <td>' . get_string('testscore', 'theme_moove') . '</td>
                                <td class="text-right color-1890FF">' . $grade . '</td>
                            </tr>
                            <tr>
                                <td>' . get_string('numberanswerright', 'theme_moove') . '</td>
                                <td class="text-right color-1890FF">' . $correctAnswer . '/' . $totalQuesetion . '</td>
                            </tr>
                        </tbody>
                    </table>
                     <a class="btn w-100 my-3" style="border-radius: 8px;background: #1890FF;" href="'.(new moodle_url('/mod/quiz/view.php', ['id'=>$quizCm->id]))->out().'">
                        <span style="text-decoration:none;color: #FFF;font-size: 16px;font-weight: 600;" >'.get_string('finishreview','theme_moove').'</span>
                    </a>
                    <button class="btn w-100 mb-3" style="border-radius: 8px;background: #D8D8D8;" data-toggle="modal" data-target=".custom-course-modal-quiz-review">
                        <i>
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="29" viewBox="0 0 28 29" fill="none">
                              <path d="M14.875 9.25003V14.0046L18.8256 16.3747C19.0246 16.4942 19.168 16.6879 19.2242 16.9131C19.2804 17.1383 19.2448 17.3767 19.1253 17.5757C19.0058 17.7747 18.8121 17.918 18.5869 17.9742C18.3617 18.0304 18.1234 17.9949 17.9244 17.8753L13.5494 15.2503C13.4199 15.1726 13.3127 15.0626 13.2384 14.9311C13.164 14.7996 13.125 14.6511 13.125 14.5V9.25003C13.125 9.01797 13.2172 8.79541 13.3813 8.63131C13.5454 8.46722 13.7679 8.37503 14 8.37503C14.2321 8.37503 14.4546 8.46722 14.6187 8.63131C14.7828 8.79541 14.875 9.01797 14.875 9.25003ZM14 4.00003C12.6197 3.99659 11.2524 4.26694 9.97725 4.79544C8.70211 5.32393 7.54444 6.10007 6.57125 7.07894C5.77609 7.88394 5.06953 8.65831 4.375 9.46878V7.50003C4.375 7.26797 4.28281 7.04541 4.11872 6.88131C3.95462 6.71722 3.73206 6.62503 3.5 6.62503C3.26794 6.62503 3.04538 6.71722 2.88128 6.88131C2.71719 7.04541 2.625 7.26797 2.625 7.50003V11.875C2.625 12.1071 2.71719 12.3297 2.88128 12.4938C3.04538 12.6578 3.26794 12.75 3.5 12.75H7.875C8.10706 12.75 8.32962 12.6578 8.49372 12.4938C8.65781 12.3297 8.75 12.1071 8.75 11.875C8.75 11.643 8.65781 11.4204 8.49372 11.2563C8.32962 11.0922 8.10706 11 7.875 11H5.35938C6.14141 10.0791 6.92016 9.21175 7.80828 8.31269C9.02437 7.0966 10.5719 6.26585 12.2574 5.92424C13.9429 5.58263 15.6918 5.7453 17.2854 6.39194C18.879 7.03857 20.2467 8.14049 21.2176 9.56001C22.1886 10.9795 22.7197 12.6537 22.7445 14.3733C22.7694 16.0929 22.287 17.7818 21.3575 19.2288C20.4281 20.6758 19.0928 21.8168 17.5185 22.5093C15.9443 23.2017 14.2009 23.4149 12.5062 23.1222C10.8115 22.8295 9.2406 22.0439 7.98984 20.8635C7.90625 20.7845 7.80791 20.7227 7.70045 20.6817C7.59299 20.6407 7.4785 20.6213 7.36353 20.6245C7.24856 20.6278 7.13536 20.6537 7.03038 20.7007C6.92541 20.7477 6.83072 20.8149 6.75172 20.8985C6.67272 20.9821 6.61096 21.0804 6.56997 21.1879C6.52897 21.2953 6.50954 21.4098 6.51279 21.5248C6.51604 21.6398 6.54191 21.753 6.58891 21.8579C6.63591 21.9629 6.70312 22.0576 6.78672 22.1366C8.03299 23.3127 9.54817 24.1659 11.2 24.6217C12.8518 25.0775 14.5901 25.1222 16.2632 24.7517C17.9363 24.3812 19.4932 23.6069 20.7982 22.4964C22.1032 21.3858 23.1165 19.9727 23.7498 18.3805C24.3831 16.7882 24.6171 15.0651 24.4313 13.3616C24.2455 11.6582 23.6456 10.026 22.684 8.60771C21.7224 7.18938 20.4283 6.02796 18.9146 5.22479C17.4008 4.42163 15.7136 4.00115 14 4.00003Z" fill="black"/>
                            </svg>
                        </i>
                        ' . get_string('viewattempthistory', 'theme_moove') . '
                    </button>

                    <div class="modal fade custom-course-modal-quiz-review" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                        <div class="modal-dialog" style="">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">' . get_string('attempthistory', 'theme_moove') . '</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                   ' . $htmlHistoryAttempts . '
                                </div>
                            </div>
                        </div>

                    </div>

                    ';
        $output .= $navbc;
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('form');

        return $output;
    }

    /**
     * Returns either a link or button.
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     */
    public function finish_review_link(quiz_attempt $attemptobj) {
        $url = $attemptobj->view_url();

        if ($attemptobj->get_access_manager(time())->attempt_must_be_in_popup()) {
            $this->page->requires->js_init_call('M.mod_quiz.secure_window.init_close_button',
                [$url->out(false)], false, quiz_get_js_module());
            return html_writer::empty_tag('input', ['type'  => 'button',
                                                    'value' => get_string('finishreview', 'quiz'),
                                                    'id'    => 'secureclosebutton',
                                                    'class' => 'mod_quiz-next-nav btn btn-primary']);

        } else {
            return html_writer::link($url, get_string('finishreview', 'quiz'),
                ['class' => 'mod_quiz-next-nav']);
        }
    }

    /**
     * Creates the navigation links/buttons at the bottom of the review attempt page.
     *
     * Note, the name of this function is no longer accurate, but when the design
     * changed, it was decided to keep the old name for backwards compatibility.
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param int $page the current page
     * @param bool $lastpage if true current page is the last page
     * @param bool|null $showall if true, the URL will be to review the entire attempt on one page,
     *      and $page will be ignored. If null, a sensible default will be chosen.
     *
     * @return string HTML fragment.
     */
    public function review_next_navigation(quiz_attempt $attemptobj, $page, $lastpage, $showall = null) {
        $nav = '';
        if ($page > 0) {
            $nav .= link_arrow_left(get_string('navigateprevious', 'quiz'),
                $attemptobj->review_url(null, $page - 1, $showall), false, 'mod_quiz-prev-nav');
        }
        if ($lastpage) {
            $nav .= $this->finish_review_link($attemptobj);
        } else {
            $nav .= link_arrow_right(get_string('navigatenext', 'quiz'),
                $attemptobj->review_url(null, $page + 1, $showall), false, 'mod_quiz-next-nav');
        }
        return html_writer::tag('div', $nav, ['class' => 'submitbtns']);
    }

    /**
     * Return the HTML of the quiz timer.
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param int $timenow timestamp to use as 'now'.
     * @return string HTML content.
     */
    public function countdown_timer(quiz_attempt $attemptobj, $timenow) {

        $timeleft = $attemptobj->get_time_left_display($timenow);
        if ($timeleft !== false) {
            $ispreview = $attemptobj->is_preview();
            $timerstartvalue = $timeleft;
            if (!$ispreview) {
                // Make sure the timer starts just above zero. If $timeleft was <= 0, then
                // this will just have the effect of causing the quiz to be submitted immediately.
                $timerstartvalue = max($timerstartvalue, 1);
            }
            $this->initialise_timer($timerstartvalue, $ispreview);
        }

        return $this->output->render_from_template('mod_quiz/timer', (object)[]);
    }

    /**
     * Create a preview link
     *
     * @param moodle_url $url URL to restart the attempt.
     */
    public function restart_preview_button($url) {
        return $this->single_button($url, get_string('startnewpreview', 'quiz'));
    }

    /**
     * Outputs the navigation block panel
     *
     * @param navigation_panel_base $panel
     */
    public function navigation_panel(navigation_panel_base $panel) {

        $output = '';
        $userpicture = $panel->user_picture();
        if ($userpicture) {
            $fullname = fullname($userpicture->user);
            if ($userpicture->size) {
                $fullname = html_writer::div($fullname);
            }
            $output .= html_writer::tag('div', $this->render($userpicture) . $fullname,
                ['id' => 'user-picture', 'class' => 'clearfix']);
        }
        $output .= $panel->render_before_button_bits($this);

        $bcc = $panel->get_button_container_class();
        $output .= html_writer::start_tag('div', ['class' => "qn_buttons justify-content-center $bcc"]);
        foreach ($panel->get_question_buttons() as $button) {
            $output .= $this->render($button);
        }
        $output .= html_writer::end_tag('div');

        $output .= html_writer::tag('div', $panel->render_end_bits($this),
            ['class' => 'othernav']);

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false,
            quiz_get_js_module());

        return $output;
    }

    /**
     * Display a quiz navigation button.
     *
     * @param navigation_question_button $button
     * @return string HTML fragment.
     */
    protected function render_navigation_question_button(navigation_question_button $button) {
        $classes = ['qnbutton', $button->stateclass, $button->navmethod, 'btn',$button->id];
        $extrainfo = [];

        if ($button->currentpage) {
            $classes[] = 'thispage';
            $extrainfo[] = get_string('onthispage', 'quiz');
        }

        // Flagged?
        if ($button->flagged) {
            $classes[] = 'flagged';
            $flaglabel = get_string('flagged', 'question');
        } else {
            $flaglabel = '';
        }
        $extrainfo[] = html_writer::tag('span', $flaglabel, ['class' => 'flagstate']);

        if ($button->isrealquestion) {
            $qnostring = 'questionnonav';
        } else {
            $qnostring = 'questionnonavinfo';
        }

        $tooltip = get_string('questionx', 'question', s($button->number)) . ' - ' . $button->statestring;

        $a = new stdClass();
        $a->number = s($button->number);
        $a->attributes = implode(' ', $extrainfo);
        $tagcontents = html_writer::tag('span', '', ['class' => 'thispageholder']) .
            html_writer::tag('span', '', ['class' => 'trafficlight']) .
            get_string($qnostring, 'quiz', $a);
        $tagattributes = ['class' => implode(' ', $classes), 'id' => $button->id,
                          'title' => $tooltip, 'data-quiz-page' => $button->page];

        if ($button->url) {
            return html_writer::link($button->url, $tagcontents, $tagattributes);
        } else {
            return html_writer::tag('span', $tagcontents, $tagattributes);
        }
    }

    /**
     * Display a quiz navigation heading.
     *
     * @param navigation_section_heading $heading the heading.
     * @return string HTML fragment.
     */
    protected function render_navigation_section_heading(navigation_section_heading $heading) {
        if (empty($heading->heading)) {
            $headingtext = get_string('sectionnoname', 'quiz');
            $class = ' dimmed_text';
        } else {
            $headingtext = $heading->heading;
            $class = '';
        }
        return $this->heading($headingtext, 3, 'mod_quiz-section-heading' . $class);
    }

    /**
     * Renders a list of links the other attempts.
     *
     * @param links_to_other_attempts $links
     * @return string HTML fragment.
     */
    protected function render_links_to_other_attempts(
        links_to_other_attempts $links) {
        $attemptlinks = [];
        foreach ($links->links as $attempt => $url) {
            if (!$url) {
                $attemptlinks[] = html_writer::tag('strong', $attempt);
            } else {
                if ($url instanceof renderable) {
                    $attemptlinks[] = $this->render($url);
                } else {
                    $attemptlinks[] = html_writer::link($url, $attempt);
                }
            }
        }
        return implode(', ', $attemptlinks);
    }

    /**
     * Render the 'start attempt' page.
     *
     * The student gets here if their interaction with the preflight check
     * from fails in some way (e.g. they typed the wrong password).
     *
     * @param \mod_quiz\quiz_settings $quizobj
     * @param preflight_check_form $mform
     * @return string
     */
    public function start_attempt_page(quiz_settings $quizobj, preflight_check_form $mform) {
        $output = '';
        $output .= $this->header();
        $output .= $this->during_attempt_tertiary_nav($quizobj->view_url());
        $output .= $this->heading(format_string($quizobj->get_quiz_name(), true,
            ["context" => $quizobj->get_context()]));
        $output .= $this->quiz_intro($quizobj->get_quiz(), $quizobj->get_cm());
        $output .= $mform->render();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Attempt Page
     *
     * @param quiz_attempt $attemptobj Instance of quiz_attempt
     * @param int $page Current page number
     * @param access_manager $accessmanager Instance of access_manager
     * @param array $messages An array of messages
     * @param array $slots Contains an array of integers that relate to questions
     * @param int $id The ID of an attempt
     * @param int $nextpage The number of the next page
     * @return string HTML to output.
     */
    public function attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id,
                                 $nextpage) {
        $output = '';
        $output .= $this->header();
        $output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
        $output .= $this->quiz_notices($messages);
        $demo =$this->countdown_timer($attemptobj, time());
        $output .= $this->countdown_timer($attemptobj, time());
        $output .= $this->attempt_form($attemptobj, $page, $slots, $id, $nextpage);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Render the tertiary navigation for pages during the attempt.
     *
     * @param string|moodle_url $quizviewurl url of the view.php page for this quiz.
     * @return string HTML to output.
     */
    public function during_attempt_tertiary_nav($quizviewurl): string {
        $output = '';
        $output .= html_writer::start_div('container-fluid tertiary-navigation');
        $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('navitem');
        $output .= html_writer::link($quizviewurl, get_string('back'),
            ['class' => 'btn btn-secondary']);
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * Returns any notices.
     *
     * @param array $messages
     */
    public function quiz_notices($messages) {
        if (!$messages) {
            return '';
        }
        return $this->notification(
            html_writer::tag('p', get_string('accessnoticesheader', 'quiz')) . $this->access_messages($messages),
            'warning',
            false
        );
    }

    /**
     * Outputs the form for making an attempt
     *
     * @param quiz_attempt $attemptobj
     * @param int $page Current page number
     * @param array $slots Array of integers relating to questions
     * @param int $id ID of the attempt
     * @param int $nextpage Next page number
     */
    public function attempt_form($attemptobj, $page, $slots, $id, $nextpage) {
        global $PAGE;
        $output = '';
        $navbc = $attemptobj->get_navigation_panel($PAGE->get_renderer('mod_quiz'), navigation_panel_attempt::class, $page);
        $summaryLink = $attemptobj->summary_url();
        $navbc = '<div>
                    <div class="mb-3">
                        <p style="margin-bottom:0;color: #000;text-align: center;font-size: 20px;font-weight: 400;">'.get_string('numberquestioncompleted','theme_moove').'</p>
                        <p style="margin-bottom:0;color: #1890FF;text-align: center;font-size: 20px;font-weight: 400;"><i class="numberquestiondo">0</i>/'.count($attemptobj->get_slots()).'</p>
                    </div>
                    <a class="endtestlink aalink mb-3 btn" style="display: block" href="'.$summaryLink.'">'.get_string('submit','theme_moove').'</a>
                  </div>'.$navbc->content;
        // Start the form.
        $output .= html_writer::start_tag('form',
            ['action'                                          => new moodle_url($attemptobj->processattempt_url(),
                ['cmid' => $attemptobj->get_cmid()]), 'method' => 'post', 'class' => 'custom-form-quiz',
             'enctype'                                         => 'multipart/form-data', 'accept-charset' => 'utf-8',
             'id'                                              => 'responseform']);
        $output .= html_writer::start_tag('div', ['class' => 'custom-list-question']);

        // Print all the questions.
        foreach ($slots as $slot) {
            $output .= $attemptobj->render_question($slot, false, $this,
                $attemptobj->attempt_url($slot, $page));
        }

        $navmethod = $attemptobj->get_quiz()->navmethod;
        $output .= $this->attempt_navigation_buttons($page, $attemptobj->is_last_page($page), $navmethod);

        // Some hidden fields to track what is going on.
        $output .= html_writer::empty_tag('input', ['type'  => 'hidden', 'name' => 'attempt',
                                                    'value' => $attemptobj->get_attemptid()]);
        $output .= html_writer::empty_tag('input', ['type'  => 'hidden', 'name' => 'thispage',
                                                    'value' => $page, 'id' => 'followingpage']);
        $output .= html_writer::empty_tag('input', ['type'  => 'hidden', 'name' => 'nextpage',
                                                    'value' => $nextpage]);
        $output .= html_writer::empty_tag('input', ['type'  => 'hidden', 'name' => 'timeup',
                                                    'value' => '0', 'id' => 'timeup']);
        $output .= html_writer::empty_tag('input', ['type'  => 'hidden', 'name' => 'sesskey',
                                                    'value' => sesskey()]);
        $output .= html_writer::empty_tag('input', ['type'  => 'hidden', 'name' => 'mdlscrollto',
                                                    'value' => '', 'id' => 'mdlscrollto']);

        // Add a hidden field with questionids. Do this at the end of the form, so
        // if you navigate before the form has finished loading, it does not wipe all
        // the student's answers.
        $output .= html_writer::empty_tag('input', ['type'  => 'hidden', 'name' => 'slots',
                                                    'value' => implode(',', $attemptobj->get_active_slots($page))]);

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', ['class' => 'custom-list-question-minimap hidden-md']);
        $output .= "<div style='position: sticky; top: 1em'>";
        $output .= $navbc;
        $output .= "</div>";
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('form');

        $output .= $this->connection_warning();

        return $output;
    }

    /**
     * Display the prev/next buttons that go at the bottom of each page of the attempt.
     *
     * @param int $page the page number. Starts at 0 for the first page.
     * @param bool $lastpage is this the last page in the quiz?
     * @param string $navmethod Optional quiz attribute, 'free' (default) or 'sequential'
     * @return string HTML fragment.
     */
    protected function attempt_navigation_buttons($page, $lastpage, $navmethod = 'free') {
        $output = '';

        $output .= html_writer::start_tag('div', ['class' => 'submitbtns']);
        if ($page > 0 && $navmethod == 'free') {
            $output .= html_writer::empty_tag('input', ['type'  => 'submit', 'name' => 'previous',
                                                        'value' => get_string('navigateprevious', 'quiz'), 'class' => 'mod_quiz-prev-nav btn btn-secondary',
                                                        'id'    => 'mod_quiz-prev-nav']);
            $this->page->requires->js_call_amd('core_form/submit', 'init', ['mod_quiz-prev-nav']);
        }
        if ($lastpage) {
            $nextlabel = get_string('endtest', 'quiz');
        } else {
            $nextlabel = get_string('navigatenext', 'quiz');
        }
        $output .= html_writer::empty_tag('input', ['type'  => 'submit', 'name' => 'next',
                                                    'value' => $nextlabel, 'class' => 'mod_quiz-next-nav btn btn-primary', 'id' => 'mod_quiz-next-nav']);
        $output .= html_writer::end_tag('div');
        $this->page->requires->js_call_amd('core_form/submit', 'init', ['mod_quiz-next-nav']);

        return $output;
    }

    /**
     * Render a button which allows students to redo a question in the attempt.
     *
     * @param int $slot the number of the slot to generate the button for.
     * @param bool $disabled if true, output the button disabled.
     * @return string HTML fragment.
     */
    public function redo_question_button($slot, $disabled) {
        $attributes = ['type'                    => 'submit', 'name' => 'redoslot' . $slot,
                       'value'                   => get_string('redoquestion', 'quiz'),
                       'class'                   => 'mod_quiz-redo_question_button btn btn-secondary',
                       'id'                      => 'redoslot' . $slot . '-submit',
                       'data-savescrollposition' => 'true',
        ];
        if ($disabled) {
            $attributes['disabled'] = 'disabled';
        } else {
            $this->page->requires->js_call_amd('core_question/question_engine', 'initSubmitButton', [$attributes['id']]);
        }
        return html_writer::div(html_writer::empty_tag('input', $attributes));
    }

    /**
     * Initialise the JavaScript required to initialise the countdown timer.
     *
     * @param int $timerstartvalue time remaining, in seconds.
     * @param bool $ispreview true if this is a preview attempt.
     */
    public function initialise_timer($timerstartvalue, $ispreview) {
        $options = [$timerstartvalue, (bool)$ispreview];
        $this->page->requires->js_init_call('M.mod_quiz.timer.init', $options, false, quiz_get_js_module());
    }

    /**
     * Output a page with an optional message, and JavaScript code to close the
     * current window and redirect the parent window to a new URL.
     *
     * @param moodle_url $url the URL to redirect the parent window to.
     * @param string $message message to display before closing the window. (optional)
     * @return string HTML to output.
     */
    public function close_attempt_popup($url, $message = '') {
        $output = '';
        $output .= $this->header();
        $output .= $this->box_start();

        if ($message) {
            $output .= html_writer::tag('p', $message);
            $output .= html_writer::tag('p', get_string('windowclosing', 'quiz'));
            $delay = 5;
        } else {
            $output .= html_writer::tag('p', get_string('pleaseclose', 'quiz'));
            $delay = 0;
        }
        $this->page->requires->js_init_call('M.mod_quiz.secure_window.close',
            [$url, $delay], false, quiz_get_js_module());

        $output .= $this->box_end();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Print each message in an array, surrounded by &lt;p>, &lt;/p> tags.
     *
     * @param array $messages the array of message strings.
     * @return string HTML to output.
     */
    public function access_messages($messages) {
        $output = '';
        foreach ($messages as $message) {
            $output .= html_writer::tag('p', $message, ['class' => 'text-left']);
        }
        return $output;
    }

    /*
     * Summary Page
     */
    /**
     * Create the summary page
     *
     * @param quiz_attempt $attemptobj
     * @param display_options $displayoptions
     */
    public function summary_page($attemptobj, $displayoptions) {
        $output = '';
        $output .= $this->header();
        $output .= $this->during_attempt_tertiary_nav($attemptobj->view_url());
        $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
        $output .= $this->heading(get_string('summaryofattempt', 'quiz'), 3);
        $output .= $this->summary_table($attemptobj, $displayoptions);
        $output .= $this->summary_page_controls($attemptobj);
        $output .= $this->footer();
        return $output;
    }

    /**
     * Generates the table of summarydata
     *
     * @param quiz_attempt $attemptobj
     * @param display_options $displayoptions
     */
    public function summary_table($attemptobj, $displayoptions) {
        // Prepare the summary table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofattempt boxaligncenter';
        $table->head = [get_string('question', 'quiz'), get_string('status', 'quiz')];
        $table->align = ['left', 'left'];
        $table->size = ['', ''];
        $markscolumn = $displayoptions->marks >= question_display_options::MARK_AND_MAX;
        if ($markscolumn) {
            $table->head[] = get_string('marks', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }
        $tablewidth = count($table->align);
        $table->data = [];

        // Get the summary info for each question.
        $slots = $attemptobj->get_slots();
        foreach ($slots as $slot) {
            // Add a section headings if we need one here.
            $heading = $attemptobj->get_heading_before_slot($slot);
            if ($heading !== null) {
                // There is a heading here.
                $rowclasses = 'quizsummaryheading';
                if ($heading) {
                    $heading = format_string($heading);
                } else {
                    if (count($attemptobj->get_quizobj()->get_sections()) > 1) {
                        // If this is the start of an unnamed section, and the quiz has more
                        // than one section, then add a default heading.
                        $heading = get_string('sectionnoname', 'quiz');
                        $rowclasses .= ' dimmed_text';
                    }
                }
                $cell = new html_table_cell(format_string($heading));
                $cell->header = true;
                $cell->colspan = $tablewidth;
                $table->data[] = [$cell];
                $table->rowclasses[] = $rowclasses;
            }

            // Don't display information items.
            if (!$attemptobj->is_real_question($slot)) {
                continue;
            }

            // Real question, show it.
            $flag = '';
            if ($attemptobj->is_question_flagged($slot)) {
                // Quiz has custom JS manipulating these image tags - so we can't use the pix_icon method here.
                $flag = html_writer::empty_tag('img', ['src' => $this->image_url('i/flagged'),
                                                       'alt' => get_string('flagged', 'question'), 'class' => 'questionflag icon-post']);
            }
            if ($attemptobj->can_navigate_to($slot)) {
                $row = [html_writer::link($attemptobj->attempt_url($slot),
                    $attemptobj->get_question_number($slot) . $flag),
                    $attemptobj->get_question_status($slot, $displayoptions->correctness)];
            } else {
                $row = [$attemptobj->get_question_number($slot) . $flag,
                    $attemptobj->get_question_status($slot, $displayoptions->correctness)];
            }
            if ($markscolumn) {
                $row[] = $attemptobj->get_question_mark($slot);
            }
            $table->data[] = $row;
            $table->rowclasses[] = 'quizsummary' . $slot . ' ' . $attemptobj->get_question_state_class(
                    $slot, $displayoptions->correctness);
        }

        // Print the summary table.
        return html_writer::table($table);
    }

    /**
     * Creates any controls the page should have.
     *
     * @param quiz_attempt $attemptobj
     */
    public function summary_page_controls($attemptobj) {
        $output = '';

        // Return to place button.
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button = new single_button(
                new moodle_url($attemptobj->attempt_url(null, $attemptobj->get_currentpage())),
                get_string('returnattempt', 'quiz'));
            $output .= $this->container($this->container($this->render($button),
                'controls'), 'submitbtns mdl-align');
        }

        // Finish attempt button.
        $options = [
            'attempt'       => $attemptobj->get_attemptid(),
            'finishattempt' => 1,
            'timeup'        => 0,
            'slots'         => '',
            'cmid'          => $attemptobj->get_cmid(),
            'sesskey'       => sesskey(),
        ];

        $button = new single_button(
            new moodle_url($attemptobj->processattempt_url(), $options),
            get_string('submitallandfinish', 'quiz'));
        $button->class = 'btn-finishattempt';
        $button->formid = 'frm-finishattempt';
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $totalunanswered = 0;
            if ($attemptobj->get_quiz()->navmethod == 'free') {
                // Only count the unanswered question if the navigation method is set to free.
                $totalunanswered = $attemptobj->get_number_of_unanswered_questions();
            }
            $this->page->requires->js_call_amd('mod_quiz/submission_confirmation', 'init', [$totalunanswered]);
        }
        $button->type = \single_button::BUTTON_PRIMARY;

        $duedate = $attemptobj->get_due_date();
        $message = '';
        if ($attemptobj->get_state() == quiz_attempt::OVERDUE) {
            $message = get_string('overduemustbesubmittedby', 'quiz', userdate($duedate));

        } else {
            if ($duedate) {
                $message = get_string('mustbesubmittedby', 'quiz', userdate($duedate));
            }
        }

        $output .= $this->countdown_timer($attemptobj, time());
        $output .= $this->container($message . $this->container(
                $this->render($button), 'controls'), 'submitbtns mdl-align');

        return $output;
    }

    /*
     * View Page
     */
    /**
     * Generates the view page
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param view_page $viewobj
     * @return string HTML to display
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        $output = '';

        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_table($quiz, $context, $viewobj);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');
        return $output;
    }

    /**
     * Render the tertiary navigation for the view page.
     *
     * @param view_page $viewobj the information required to display the view page.
     * @return string HTML to output.
     */
    public function view_page_tertiary_nav(view_page $viewobj): string {
        $content = '';

        if ($viewobj->buttontext) {
            $attemptbtn = $this->start_attempt_button($viewobj->buttontext,
                $viewobj->startattempturl, $viewobj->preflightcheckform,
                $viewobj->popuprequired, $viewobj->popupoptions);
            $content .= $attemptbtn;
        }

        if ($viewobj->canedit && !$viewobj->quizhasquestions) {
            $content .= html_writer::link($viewobj->editurl, get_string('addquestion', 'quiz'),
                ['class' => 'btn btn-secondary']);
        }

        if ($content) {
            return html_writer::div(html_writer::div($content, 'row'), 'container-fluid tertiary-navigation');
        } else {
            return '';
        }
    }

    /**
     * Work out, and render, whatever buttons, and surrounding info, should appear
     * at the end of the review page.
     *
     * @param view_page $viewobj the information required to display the view page.
     * @return string HTML to output.
     */
    public function view_page_buttons(view_page $viewobj) {
        $output = '';

        if (!$viewobj->quizhasquestions) {
            $output .= html_writer::div(
                $this->notification(get_string('noquestions', 'quiz'), 'warning', false),
                'text-left mb-3');
        }
        $output .= $this->access_messages($viewobj->preventmessages);

        if ($viewobj->showbacktocourse) {
            $output .= $this->single_button($viewobj->backtocourseurl,
                get_string('backtocourse', 'quiz'), 'get',
                ['class' => 'continuebutton']);
        }

        return $output;
    }

    /**
     * Generates the view attempt button
     *
     * @param string $buttontext the label to display on the button.
     * @param moodle_url $url The URL to POST to in order to start the attempt.
     * @param preflight_check_form|null $preflightcheckform deprecated.
     * @param bool $popuprequired whether the attempt needs to be opened in a pop-up.
     * @param array $popupoptions the options to use if we are opening a popup.
     * @return string HTML fragment.
     */
    public function start_attempt_button($buttontext, moodle_url $url,
                                         preflight_check_form $preflightcheckform = null,
        $popuprequired = false, $popupoptions = null) {

        $button = new single_button($url, $buttontext, 'post', single_button::BUTTON_PRIMARY);
        $button->class .= ' quizstartbuttondiv';
        if ($popuprequired) {
            $button->class .= ' quizsecuremoderequired';
        }

        $popupjsoptions = null;
        if ($popuprequired && $popupoptions) {
            $action = new popup_action('click', $url, 'popup', $popupoptions);
            $popupjsoptions = $action->get_js_options();
        }

        $this->page->requires->js_call_amd('mod_quiz/preflightcheck', 'init',
            ['.quizstartbuttondiv [type=submit]', get_string('startattempt', 'quiz'),
                '#mod_quiz_preflight_form', $popupjsoptions]);

        return $this->render($button) . ($preflightcheckform ? $preflightcheckform->render() : '');
    }

    /**
     * Generate a message saying that this quiz has no questions, with a button to
     * go to the edit page, if the user has the right capability.
     *
     * @param bool $canedit can the current user edit the quiz?
     * @param moodle_url $editurl URL of the edit quiz page.
     * @return string HTML to output.
     *
     * @deprecated since Moodle 4.0 MDL-71915 - please do not use this function any more.
     */
    public function no_questions_message($canedit, $editurl) {
        debugging('no_questions_message() is deprecated, please use generate_no_questions_message() instead.', DEBUG_DEVELOPER);

        $output = html_writer::start_tag('div', ['class' => 'card text-center mb-3']);
        $output .= html_writer::start_tag('div', ['class' => 'card-body']);

        $output .= $this->notification(get_string('noquestions', 'quiz'), 'warning', false);
        if ($canedit) {
            $output .= $this->single_button($editurl, get_string('editquiz', 'quiz'), 'get');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Outputs an error message for any guests accessing the quiz
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param array $messages Array containing any messages
     * @param view_page $viewobj
     */
    public function view_page_guest($course, $quiz, $cm, $context, $messages, $viewobj) {
        $output = '';
        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $messages);
        $guestno = html_writer::tag('p', get_string('guestsno', 'quiz'));
        $liketologin = html_writer::tag('p', get_string('liketologin'));
        $referer = get_local_referer(false);
        $output .= $this->confirm($guestno . "\n\n" . $liketologin . "\n", get_login_url(), $referer);
        return $output;
    }

    /**
     * Outputs and error message for anyone who is not enrolled on the course.
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param array $messages Array containing any messages
     * @param view_page $viewobj
     */
    public function view_page_notenrolled($course, $quiz, $cm, $context, $messages, $viewobj) {
        global $CFG;
        $output = '';
        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $messages);
        $youneedtoenrol = html_writer::tag('p', get_string('youneedtoenrol', 'quiz'));
        $button = html_writer::tag('p',
            $this->continue_button($CFG->wwwroot . '/course/view.php?id=' . $course->id));
        $output .= $this->box($youneedtoenrol . "\n\n" . $button . "\n", 'generalbox', 'notice');
        return $output;
    }

    /**
     * Output the page information
     *
     * @param stdClass $quiz the quiz settings.
     * @param cm_info|stdClass $cm the course_module object.
     * @param context $context the quiz context.
     * @param array $messages any access messages that should be described.
     * @param bool $quizhasquestions does quiz has questions added.
     * @return string HTML to output.
     */
    public function view_information($quiz, $cm, $context, $messages, bool $quizhasquestions = false) {
        $output = '';

        // Output any access messages.
        if ($messages) {
            $output .= $this->box($this->access_messages($messages), 'quizinfo');
        }

        // Show number of attempts summary to those who can view reports.
        if (has_capability('mod/quiz:viewreports', $context)) {
            if ($strattemptnum = $this->quiz_attempt_summary_link_to_reports($quiz, $cm,
                $context)) {
                $output .= html_writer::tag('div', $strattemptnum,
                    ['class' => 'quizattemptcounts']);
            }
        }

        if (has_any_capability(['mod/quiz:manageoverrides', 'mod/quiz:viewoverrides'], $context)) {
            if ($overrideinfo = $this->quiz_override_summary_links($quiz, $cm)) {
                $output .= html_writer::tag('div', $overrideinfo, ['class' => 'quizattemptcounts']);
            }
        }

        return $output;
    }

    /**
     * Output the quiz intro.
     *
     * @param stdClass $quiz the quiz settings.
     * @param stdClass $cm the course_module object.
     * @return string HTML to output.
     */
    public function quiz_intro($quiz, $cm) {
        if (html_is_blank($quiz->intro)) {
            return '';
        }

        return $this->box(format_module_intro('quiz', $quiz, $cm->id), 'generalbox', 'intro');
    }

    /**
     * Generates the table heading.
     */
    public function view_table_heading() {
        return $this->heading(get_string('summaryofattempts', 'quiz'), 3);
    }

    /**
     * Generates the table of data
     *
     * @param stdClass $quiz the quiz settings.
     * @param context_module $context the quiz context.
     * @param view_page $viewobj
     */
    public function view_table($quiz, $context, $viewobj) {
        if (!$viewobj->attempts) {
            return '';
        }

        // Prepare table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizattemptsummary';
        $table->head = [];
        $table->align = [];
        $table->size = [];
        if ($viewobj->attemptcolumn) {
            $table->head[] = get_string('attemptnumber', 'quiz');
            $table->align[] = 'center';
            $table->size[] = '';
        }
        $table->head[] = get_string('attemptstate', 'quiz');
        $table->align[] = 'left';
        $table->size[] = '';
        if ($viewobj->markcolumn) {
            $table->head[] = get_string('marks', 'quiz') . ' / ' .
                quiz_format_grade($quiz, $quiz->sumgrades);
            $table->align[] = 'center';
            $table->size[] = '';
        }
        if ($viewobj->gradecolumn) {
            $table->head[] = get_string('gradenoun') . ' / ' .
                quiz_format_grade($quiz, $quiz->grade);
            $table->align[] = 'center';
            $table->size[] = '';
        }
        if ($viewobj->canreviewmine) {
            $table->head[] = get_string('review', 'quiz');
            $table->align[] = 'center';
            $table->size[] = '';
        }
        if ($viewobj->feedbackcolumn) {
            $table->head[] = get_string('feedback', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }

        // One row for each attempt.
        foreach ($viewobj->attemptobjs as $attemptobj) {
            $attemptoptions = $attemptobj->get_display_options(true);
            $row = [];

            // Add the attempt number.
            if ($viewobj->attemptcolumn) {
                if ($attemptobj->is_preview()) {
                    $row[] = get_string('preview', 'quiz');
                } else {
                    $row[] = $attemptobj->get_attempt_number();
                }
            }

            $row[] = $this->attempt_state($attemptobj);

            if ($viewobj->markcolumn) {
                if ($attemptoptions->marks >= question_display_options::MARK_AND_MAX &&
                    $attemptobj->is_finished()) {
                    $row[] = quiz_format_grade($quiz, $attemptobj->get_sum_marks());
                } else {
                    $row[] = '';
                }
            }

            // Outside the if because we may be showing feedback but not grades.
            $attemptgrade = quiz_rescale_grade($attemptobj->get_sum_marks(), $quiz, false);

            if ($viewobj->gradecolumn) {
                if ($attemptoptions->marks >= question_display_options::MARK_AND_MAX &&
                    $attemptobj->is_finished()) {

                    // Highlight the highest grade if appropriate.
                    if ($viewobj->overallstats && !$attemptobj->is_preview()
                        && $viewobj->numattempts > 1 && !is_null($viewobj->mygrade)
                        && $attemptobj->get_state() == quiz_attempt::FINISHED
                        && $attemptgrade == $viewobj->mygrade
                        && $quiz->grademethod == QUIZ_GRADEHIGHEST) {
                        $table->rowclasses[$attemptobj->get_attempt_number()] = 'bestrow';
                    }

                    $row[] = quiz_format_grade($quiz, $attemptgrade);
                } else {
                    $row[] = '';
                }
            }

            if ($viewobj->canreviewmine) {
                $row[] = $viewobj->accessmanager->make_review_link($attemptobj->get_attempt(),
                    $attemptoptions, $this);
            }

            if ($viewobj->feedbackcolumn && $attemptobj->is_finished()) {
                if ($attemptoptions->overallfeedback) {
                    $row[] = quiz_feedback_for_grade($attemptgrade, $quiz, $context);
                } else {
                    $row[] = '';
                }
            }

            if ($attemptobj->is_preview()) {
                $table->data['preview'] = $row;
            } else {
                $table->data[$attemptobj->get_attempt_number()] = $row;
            }
        } // End of loop over attempts.

        $output = '';
        $output .= $this->view_table_heading();
        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * Generate a brief textual description of the current state of an attempt.
     *
     * @param quiz_attempt $attemptobj the attempt
     * @return string the appropriate lang string to describe the state.
     */
    public function attempt_state($attemptobj) {
        switch ($attemptobj->get_state()) {
            case quiz_attempt::IN_PROGRESS:
                return get_string('stateinprogress', 'quiz');

            case quiz_attempt::OVERDUE:
                return get_string('stateoverdue', 'quiz') . html_writer::tag('span',
                        get_string('stateoverduedetails', 'quiz',
                            userdate($attemptobj->get_due_date())),
                        ['class' => 'statedetails']);

            case quiz_attempt::FINISHED:
                return get_string('statefinished', 'quiz') . html_writer::tag('span',
                        get_string('statefinisheddetails', 'quiz',
                            userdate($attemptobj->get_submitted_date())),
                        ['class' => 'statedetails']);

            case quiz_attempt::ABANDONED:
                return get_string('stateabandoned', 'quiz');

            default:
                throw new coding_exception('Unexpected attempt state');
        }
    }

    /**
     * Generates data pertaining to quiz results
     *
     * @param stdClass $quiz Array containing quiz data
     * @param context_module $context The quiz context.
     * @param stdClass|cm_info $cm The course module information.
     * @param view_page $viewobj
     * @return string HTML to display.
     */
    public function view_result_info($quiz, $context, $cm, $viewobj) {
        $output = '';
        if (!$viewobj->numattempts && !$viewobj->gradecolumn && is_null($viewobj->mygrade)) {
            return $output;
        }
        $resultinfo = '';

        if ($viewobj->overallstats) {
            if ($viewobj->moreattempts) {
                $a = new stdClass();
                $a->method = quiz_get_grading_option_name($quiz->grademethod);
                $a->mygrade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->quizgrade = quiz_format_grade($quiz, $quiz->grade);
                $resultinfo .= $this->heading(get_string('gradesofar', 'quiz', $a), 3);
            } else {
                $a = new stdClass();
                $a->grade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                $a = get_string('outofshort', 'quiz', $a);
                $resultinfo .= $this->heading(get_string('yourfinalgradeis', 'quiz', $a), 3);
            }
        }

        if ($viewobj->mygradeoverridden) {

            $resultinfo .= html_writer::tag('p', get_string('overriddennotice', 'grades'),
                    ['class' => 'overriddennotice']) . "\n";
        }
        if ($viewobj->gradebookfeedback) {
            $resultinfo .= $this->heading(get_string('comment', 'quiz'), 3);
            $resultinfo .= html_writer::div($viewobj->gradebookfeedback, 'quizteacherfeedback') . "\n";
        }
        if ($viewobj->feedbackcolumn) {
            $resultinfo .= $this->heading(get_string('overallfeedback', 'quiz'), 3);
            $resultinfo .= html_writer::div(
                    quiz_feedback_for_grade($viewobj->mygrade, $quiz, $context),
                    'quizgradefeedback') . "\n";
        }

        if ($resultinfo) {
            $output .= $this->box($resultinfo, 'generalbox', 'feedback');
        }
        return $output;
    }

    /**
     * Output either a link to the review page for an attempt, or a button to
     * open the review in a popup window.
     *
     * @param moodle_url $url of the target page.
     * @param bool $reviewinpopup whether a pop-up is required.
     * @param array $popupoptions options to pass to the popup_action constructor.
     * @return string HTML to output.
     */
    public function review_link($url, $reviewinpopup, $popupoptions) {
        if ($reviewinpopup) {
            $button = new single_button($url, get_string('review', 'quiz'));
            $button->add_action(new popup_action('click', $url, 'quizpopup', $popupoptions));
            return $this->render($button);

        } else {
            return html_writer::link($url, get_string('review', 'quiz'),
                ['title' => get_string('reviewthisattempt', 'quiz')]);
        }
    }

    /**
     * Displayed where there might normally be a review link, to explain why the
     * review is not available at this time.
     *
     * @param string $message optional message explaining why the review is not possible.
     * @return string HTML to output.
     */
    public function no_review_message($message) {
        return html_writer::nonempty_tag('span', $message,
            ['class' => 'noreviewmessage']);
    }

    /**
     * Returns the same as {@see quiz_num_attempt_summary()} but wrapped in a link to the quiz reports.
     *
     * @param stdClass $quiz the quiz object. Only $quiz->id is used at the moment.
     * @param stdClass $cm the cm object. Only $cm->course, $cm->groupmode and $cm->groupingid
     * fields are used at the moment.
     * @param context $context the quiz context.
     * @param bool $returnzero if false (default), when no attempts have been made '' is returned
     *      instead of 'Attempts: 0'.
     * @param int $currentgroup if there is a concept of current group where this method is being
     *      called (e.g. a report) pass it in here. Default 0 which means no current group.
     * @return string HTML fragment for the link.
     */
    public function quiz_attempt_summary_link_to_reports($quiz, $cm, $context,
                                                         $returnzero = false, $currentgroup = 0) {
        global $CFG;
        $summary = quiz_num_attempt_summary($quiz, $cm, $returnzero, $currentgroup);
        if (!$summary) {
            return '';
        }

        require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
        $url = new moodle_url('/mod/quiz/report.php', [
            'id' => $cm->id, 'mode' => quiz_report_default_report($context)]);
        return html_writer::link($url, $summary);
    }

    /**
     * Render a summary of the number of group and user overrides, with corresponding links.
     *
     * @param stdClass $quiz the quiz settings.
     * @param cm_info|stdClass $cm the cm object.
     * @param int $currentgroup currently selected group, if there is one.
     * @return string HTML fragment for the link.
     */
    public function quiz_override_summary_links(stdClass $quiz, cm_info|stdClass $cm, $currentgroup = 0): string {

        $baseurl = new moodle_url('/mod/quiz/overrides.php', ['cmid' => $cm->id]);
        $counts = quiz_override_summary($quiz, $cm, $currentgroup);

        $links = [];
        if ($counts['group']) {
            $links[] = html_writer::link(new moodle_url($baseurl, ['mode' => 'group']),
                get_string('overridessummarygroup', 'quiz', $counts['group']));
        }
        if ($counts['user']) {
            $links[] = html_writer::link(new moodle_url($baseurl, ['mode' => 'user']),
                get_string('overridessummaryuser', 'quiz', $counts['user']));
        }

        if (!$links) {
            return '';
        }

        $links = implode(', ', $links);
        switch ($counts['mode']) {
            case 'onegroup':
                return get_string('overridessummarythisgroup', 'quiz', $links);

            case 'somegroups':
                return get_string('overridessummaryyourgroups', 'quiz', $links);

            case 'allgroups':
                return get_string('overridessummary', 'quiz', $links);

            default:
                throw new coding_exception('Unexpected mode ' . $counts['mode']);
        }
    }

    /**
     * Outputs a chart.
     *
     * @param \core\chart_base $chart The chart.
     * @param string $title The title to display above the graph.
     * @param array $attrs extra container html attributes.
     * @return string HTML of the graph.
     */
    public function chart(\core\chart_base $chart, $title, $attrs = []) {
        return $this->heading($title, 3) . html_writer::tag('div',
                $this->render($chart), array_merge(['class' => 'graph'], $attrs));
    }

    /**
     * Output a graph, or a message saying that GD is required.
     *
     * @param moodle_url $url the URL of the graph.
     * @param string $title the title to display above the graph.
     * @return string HTML of the graph.
     */
    public function graph(moodle_url $url, $title) {
        $graph = html_writer::empty_tag('img', ['src' => $url, 'alt' => $title]);

        return $this->heading($title, 3) . html_writer::tag('div', $graph, ['class' => 'graph']);
    }

    /**
     * Output the connection warning messages, which are initially hidden, and
     * only revealed by JavaScript if necessary.
     */
    public function connection_warning() {
        $options = ['filter' => false, 'newlines' => false];
        $warning = format_text(get_string('connectionerror', 'quiz'), FORMAT_MARKDOWN, $options);
        $ok = format_text(get_string('connectionok', 'quiz'), FORMAT_MARKDOWN, $options);
        return html_writer::tag('div', $warning,
                ['id' => 'connection-error', 'style' => 'display: none;', 'role' => 'alert']) .
            html_writer::tag('div', $ok, ['id' => 'connection-ok', 'style' => 'display: none;', 'role' => 'alert']);
    }

    /**
     * Deprecated version of render_links_to_other_attempts.
     *
     * @param links_to_other_attempts $links
     * @return string HTML fragment.
     * @deprecated since Moodle 4.2. Please use render_links_to_other_attempts instead.
     * @todo MDL-76612 Final deprecation in Moodle 4.6
     */
    protected function render_mod_quiz_links_to_other_attempts(links_to_other_attempts $links) {
        return $this->render_links_to_other_attempts($links);
    }

    /**
     * Deprecated version of render_navigation_question_button.
     *
     * @param navigation_question_button $button
     * @return string HTML fragment.
     * @deprecated since Moodle 4.2. Please use render_links_to_other_attempts instead.
     * @todo MDL-76612 Final deprecation in Moodle 4.6
     */
    protected function render_quiz_nav_question_button(navigation_question_button $button) {
        return $this->render_navigation_question_button($button);
    }

    /**
     * Deprecated version of render_navigation_section_heading.
     *
     * @param navigation_section_heading $heading the heading.
     * @return string HTML fragment.
     * @deprecated since Moodle 4.2. Please use render_links_to_other_attempts instead.
     * @todo MDL-76612 Final deprecation in Moodle 4.6
     */
    protected function render_quiz_nav_section_heading(navigation_section_heading $heading) {
        return $this->render_navigation_section_heading($heading);
    }
}
