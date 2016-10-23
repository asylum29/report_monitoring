<?php

defined('MOODLE_INTERNAL') || die();

class report_monitoring_renderer extends plugin_renderer_base {

    public function display_report($coursesdata) {
        global $CFG, $OUTPUT;
        
        if (count($coursesdata) > 0) {
            $table = report_monitoring_table::create_table('table report_monitoring_table');
            $table->head = array(
                get_string('key1', 'report_monitoring'), 
                get_string('key2', 'report_monitoring'),
                get_string('key3', 'report_monitoring'),
                ''
            );
            foreach ($coursesdata as $coursedata) {
            
                $coursestats = $this->get_course_stats($coursedata); // получаем заранее для отображения (или нет) кнопки "развернуть"
            
                /**************************************/
                /* Основные сведения о работе в курсе */
                /**************************************/
            
                $cells = array();

                $content = $OUTPUT->pix_icon('i/course', null, '', array('class' => 'icon')) . $coursedata->fullname;
                if ($coursedata->visible) {
                    $courseurl = "$CFG->wwwroot/course/view.php?id=$coursedata->id";
                    $content = html_writer::link($courseurl, $content);
                }
                $content = $OUTPUT->heading($content, 4, 'report_monitoring_coursename');
                $cells[] = report_monitoring_table::create_cell($content);
                
                $content = '';
                if (count($coursedata->graders) > 0) {
                    foreach ($coursedata->graders as $grader) {
                        $userurl = "$CFG->wwwroot/user/view.php?id=$grader->id&course=$coursedata->id";
                        $content .= html_writer::link($userurl, $grader->fullname) . '&nbsp;';
                        $content .= $grader->lastaccess ? '(' . format_time(time() - $grader->lastaccess) . ')' : '(' . get_string('never') . ')'; 
                        $content .= '<br />';
                    }
                } else $content = get_string('key4', 'report_monitoring');
                $cells[] = report_monitoring_table::create_cell($content);
                
                $need_grading = 0;
                foreach ($coursedata->assigns as $assign) {
                    if (!$assign->teamsubmission && !$assign->nograde) {
                        $need_grading += $assign->need_grading;
                    }
                }
                
                $notices = array();
                if (count($coursedata->graders) == 0) {
                    $notices[] = get_string('key4', 'report_monitoring');
                }
                if (count($coursedata->graders) == $coursedata->participants) {
                    $notices[] = get_string('key5', 'report_monitoring');
                }
                if (count($coursedata->assigns) == 0 && count($coursedata->quiz) == 0) {
                    $notices[] = get_string('key6', 'report_monitoring');
                }
                if ($coursedata->files == 0) {
                    $notices[] = get_string('key7', 'report_monitoring');
                }
                if ($need_grading > 0) {
                    $notices[] = get_string('key9', 'report_monitoring') . '&nbsp;(' . $need_grading . ')';
                }
                $class = count($notices) > 0 ? 'report_monitoring_red' : 'report_monitoring_green'; // проверка наличия замечаний по курсу
                if (count($notices) == 0) {
                    $notices[] = get_string('key8', 'report_monitoring');
                }             
                $cells[] = report_monitoring_table::create_cell(html_writer::alist($notices));
                
                $content = $coursestats ? html_writer::div('', 'report_monitoring_showmore') : '';
                $cells[] = report_monitoring_table::create_cell($content);
                
                $row = report_monitoring_table::create_row($cells, $class);
                
                $table->data[] = $row;
                
                /***************************************/
                /* Подробные сведения о работе в курсе */
                /***************************************/
                
                if ($coursestats) {
                    $cell = report_monitoring_table::create_cell($coursestats, 'report_monitoring_coursestats');
                    $cell->colspan = 4;
                    $row = report_monitoring_table::create_row(array($cell));
                    $table->data[] = $row;
                }
            
                $cell = report_monitoring_table::create_cell('', 'report_monitoring_separator');
                $cell->colspan = 4;
                $row = report_monitoring_table::create_row(array($cell));
                $table->data[] = $row;
            
            }
            return html_writer::table($table);
        } else {
            return $OUTPUT->heading(get_string('key22', 'report_monitoring'), 3);
        }
    }
    
    public function get_course_stats($coursedata) {
        global $CFG, $OUTPUT;
    
        if (count($coursedata->assigns) > 0 || count($coursedata->quiz) > 0) {
        
            $statshtml = '';
            
            if (count($coursedata->assigns) > 0) {
                $table = report_monitoring_table::create_table('generaltable report_monitoring_coursetable');
                $table->head = array(
                    get_string('key11', 'report_monitoring'), 
                    get_string('key12', 'report_monitoring'),
                    get_string('key13', 'report_monitoring'),
                    get_string('key14', 'report_monitoring'),
                );
                $assignstotal = array('participants' => 0, 'submitted' => 0, 'graded' => 0);
                foreach ($coursedata->assigns as $modid => $assign) {
                    $cells = array();
                    
                    $content = $OUTPUT->pix_icon('icon', '', 'assign', array('class' => 'icon')) . $assign->name;
                    if ($assign->visible) {
                        $assignurl = "$CFG->wwwroot/mod/assign/view.php?id=$modid";
                        $content = html_writer::link($assignurl, $content);
                    }
                    if ($assign->nograde) {
                        $content .= $OUTPUT->pix_icon('nograde', get_string('key20', 'report_monitoring'), 'report_monitoring', array('class' => 'iconsmall'));
                    }
                    if ($assign->teamsubmission) {
                        $content .= $OUTPUT->pix_icon('i/users', get_string('key19', 'report_monitoring'), '', array('class' => 'iconsmall'));
                    }
                    $cells[] = report_monitoring_table::create_cell($content);
                    
                    $value = '—';
                    if (!$assign->teamsubmission) {
                        $value = $assign->participants;
                        $assignstotal['participants'] += $value;
                    }
                    $cells[] = report_monitoring_table::create_cell($value);
                    
                    $value = '—';
                    if (!$assign->teamsubmission) {
                        $value = $assign->submitted;
                        $assignstotal['submitted'] += $value;
                    }
                    $cells[] = report_monitoring_table::create_cell($value);
                    
                    $graded = 0;
                    $value = '—';
                    if (!$assign->teamsubmission && !$assign->nograde) {
                        $graded = $assign->submitted - $assign->need_grading;
                        $value = $graded . '&nbsp;' . ($assign->need_grading != 0 ? 
                            $OUTPUT->pix_icon('alert', get_string('key9', 'report_monitoring'), 'report_monitoring', array('class' => 'icon')) : '');
                        $assignstotal['graded'] += $graded;
                    }
                    $cells[] = report_monitoring_table::create_cell($value);
                    
                    $table->data[] = report_monitoring_table::create_row($cells);
                }
                
                $cells = array();
                $cells[] = report_monitoring_table::create_cell(get_string('key15', 'report_monitoring'));
                $cells[] = report_monitoring_table::create_cell($assignstotal['participants']);
                $cells[] = report_monitoring_table::create_cell($assignstotal['submitted']);
                $cells[] = report_monitoring_table::create_cell($assignstotal['graded']);
                $table->data[] = report_monitoring_table::create_row($cells);
                
                $statshtml .= html_writer::div(get_string('key10', 'report_monitoring') . ':', 'report_monitoring_modheader');
                $statshtml .= html_writer::table($table);               
            }
            
            if (count($coursedata->quiz) > 0) {
                $table = report_monitoring_table::create_table('generaltable report_monitoring_coursetable');
                $table->head = array(
                    get_string('key11', 'report_monitoring'), 
                    get_string('key17', 'report_monitoring'),
                    get_string('key18', 'report_monitoring'),
                );
                $quizestotal = array('countusers'  => 0, 'countgrades' => 0);
                foreach ($coursedata->quiz as $modid => $quiz) {
                    $cells = array();
                    
                    $content = $OUTPUT->pix_icon('icon', '', 'quiz', array('class' => 'icon')) . $quiz->name;
                    if ($quiz->visible) {
                        $quizurl = "$CFG->wwwroot/mod/quiz/view.php?id=$modid";
                        $content = html_writer::link($quizurl, $content);
                    }
                    $cells[] = report_monitoring_table::create_cell($content);
                    
                    $cells[] = report_monitoring_table::create_cell($quiz->countusers);
                    $quizestotal['countusers'] += $quiz->countusers;
                    
                    $cells[] = report_monitoring_table::create_cell($quiz->countgrades);
                    $quizestotal['countgrades'] += $quiz->countgrades;
                    
                    $table->data[] = report_monitoring_table::create_row($cells);
                }
                
                $cells = array();
                $cells[] = report_monitoring_table::create_cell(get_string('key15', 'report_monitoring'));
                $cells[] = report_monitoring_table::create_cell($quizestotal['countusers']);
                $cells[] = report_monitoring_table::create_cell($quizestotal['countgrades']);
                $table->data[] = report_monitoring_table::create_row($cells);
                
                $statshtml .= html_writer::div(get_string('key16', 'report_monitoring') . ':', 'report_monitoring_modheader');
                $statshtml .= html_writer::table($table);
            }
            
            return html_writer::div($statshtml, 'report_monitoring_coursestats');
            
        } else return false;
    }

}

class report_monitoring_table {

    public static function create_table($class = 'table', $cellpadding = 5) {
        $table = new html_table();
        $table->attributes['class'] = $class;
        $table->cellpadding = $cellpadding;
        return $table;
    }

    public static function create_cell($content, $class = '') {
        $cell = new html_table_cell();
        $cell->attributes['class'] = $class;
        $cell->text = $content;
        return $cell;
    }

    public static function create_row($cells, $class = '') {
        $row = new html_table_row();
        $row->attributes['class'] = $class;
        $row->cells = $cells;
        return $row;
    }

}
