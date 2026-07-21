<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/lib.php');

class block_moduleprogress extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_moduleprogress');
    }

    public function applicable_formats() {
        return [
            'course-view' => true,
            'site' => false,
            'my' => false
        ];
    }

    public function instance_allow_config() {
        return true;
    }

    public function get_content() {
        global $COURSE, $USER, $PAGE, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if (empty($COURSE->id) || $COURSE->id == SITEID) {
            $this->content->text = '';
            return $this->content;
        }

        // 1. Rollen-Prüfung: Ausschließlich User mit der Rolle 'student' ermitteln
        $context = context_course::instance($COURSE->id, IGNORE_MISSING);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $student_ids = [];

        if ($studentrole && $context) {
            $students = get_role_users($studentrole->id, $context, false, 'u.id');
            if (!empty($students)) {
                $student_ids = array_keys($students);
            }
        }

        $is_student = in_array($USER->id, $student_ids);

        // Daten für den aktuellen User berechnen
        $percent = $this->get_course_percent($USER->id, $COURSE->id);
        $status = $this->get_status($percent);
        $progress = $this->get_progress_to_next($percent);
        $recommendation = $this->get_recommendation($percent);
        $badges = $this->get_badge_preview($percent);

        $showleaderboard = !isset($this->config->showleaderboard) || !empty($this->config->showleaderboard);
        $maxrows = !empty($this->config->maxrows) ? (int)$this->config->maxrows : 5;
        $rankingcourses = $this->get_ranking_courses((int)$COURSE->id);
        
        $leaderboard = $showleaderboard ? $this->get_leaderboard_html((int)$USER->id, $rankingcourses, $maxrows, $student_ids, $is_student) : '';

        $gaugeid = 'moduleprogress-' . uniqid();

        $PAGE->requires->js_call_amd('block_moduleprogress/gauge', 'init', [
            $gaugeid,
            $percent
        ]);

        // HTML & CSS für Layout, Overlays & Toggle
        $html = '
        <style>
            .mp-relative { position: relative; }
            .mp-overlay {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255, 255, 255, 0.90);
                backdrop-filter: blur(2px);
                z-index: 20;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                text-align: center;
                border-radius: 12px;
            }
            .mp-overlay-dark {
                background: rgba(11, 19, 43, 0.92) !important;
                color: #ffffff !important;
            }
            .mp-badge-tag {
                background: #ffcc00;
                color: #0b132b;
                font-weight: bold;
                font-size: 0.75rem;
                padding: 4px 10px;
                border-radius: 6px;
                margin-bottom: 8px;
                display: inline-block;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
        </style>

        <div class="moduleprogress-widget-wrapper">
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="mp-toggle-btn" onclick="toggleModuleProgress()">
                <i class="fa fa-chevron-up" id="mp-toggle-icon"></i> <span id="mp-toggle-text">Modulfortschritt ausblenden</span>
            </button>

            <div id="moduleprogress-content-body">
                <div class="moduleprogress-widget">

                    <!-- LEFT / MIDDLE AREA -->
                    <div class="moduleprogress-left mp-relative">
                        ' . (!$is_student ? '
                        <div class="mp-overlay">
                            <div>
                                <span class="mp-badge-tag">Dozenten-Info: Prozent & Empfehlungen</span>
                                <p class="small text-dark mb-0">Teilnehmende sehen hier ihren aktuellen Kursfortschritt in % sowie dynamische Empfehlungen, um ihren Notendurchschnitt durch Aufgaben oder LSKs zu verbessern.</p>
                            </div>
                        </div>' : '') . '

                        <div class="moduleprogress-headline">
                            <span></span>
                            <div>
                                <div class="moduleprogress-title">Modulfortschritt</div>
                                <div class="moduleprogress-subtitle">Bewertung, Ziel und nächste Empfehlung</div>
                            </div>
                        </div>

                        <div class="moduleprogress-gauge-wrap">
                            <canvas id="' . $gaugeid . '" width="360" height="190"></canvas>
                            <div class="moduleprogress-percent">' . round($percent) . '<small>%</small></div>
                        </div>
                    </div>

                    <div class="moduleprogress-middle mp-relative">
                        ' . (!$is_student ? '
                        <div class="mp-overlay">
                            <div>
                                <span class="mp-badge-tag">Dozenten-Info: Status & Fortschritt</span>
                                <p class="small text-dark mb-0">Zeigt den Abstand zur nächsten Badge-Stufe (Bronze/Silber/Gold) inklusive eines Fortschrittsbalkens an.</p>
                            </div>
                        </div>' : '') . '

                        <div class="moduleprogress-status">
                            <strong>' . s($status['title']) . '</strong>
                            <span>' . s($status['message']) . '</span>
                        </div>

                        <div class="moduleprogress-next">
                            Noch <strong>' . s($progress['missing']) . ' %</strong> bis ' . s($progress['next']) . '
                        </div>

                        <div class="moduleprogress-progressbar">
                            <div style="width:' . s($progress['progress']) . '%;"></div>
                        </div>

                        <div class="moduleprogress-recommendation">
                            <strong>Empfehlung</strong>
                            <span>' . s($recommendation) . '</span>
                        </div>
                    </div>

                    <!-- RIGHT AREA: Badges -->
                    <div class="moduleprogress-right mp-relative">
                        ' . (!$is_student ? '
                        <div class="mp-overlay">
                            <div>
                                <span class="mp-badge-tag">Dozenten-Info: Badges</span>
                                <p class="small text-dark mb-0">Visualisiert das Erreichen von Meilensteinen: Bronze (70%), Silber (80%) und Gold (90%).</p>
                            </div>
                        </div>' : '') . '

                        <div class="moduleprogress-badge-title">Badge-Vorschau</div>
                        <div class="moduleprogress-badges">' . $badges . '</div>
                    </div>

                    <!-- LEADERBOARD AREA -->
                    <div class="mp-relative" style="grid-column: 1 / -1;">
                        ' . (!$is_student ? '
                        <div class="mp-overlay mp-overlay-dark" style="border-radius: 16px;">
                            <div style="max-width: 650px;">
                                <span class="mp-badge-tag">Dozenten-Info: Kohorten-Ranking</span>
                                <p class="small text-white mb-0">Anonymisierte Platzierungsliste. Es werden <b> Teilnehmende</b> anonym gewertet und oben rechts getrackt.</p>
                            </div>
                        </div>' : '') . '

                        ' . $leaderboard . '
                    </div>

                </div>
            </div>
        </div>

        <script>
        function toggleModuleProgress() {
            var body = document.getElementById("moduleprogress-content-body");
            var icon = document.getElementById("mp-toggle-icon");
            var text = document.getElementById("mp-toggle-text");
            
            if (body.style.display === "none") {
                body.style.display = "block";
                text.innerText = "Modulfortschritt ausblenden";
                icon.className = "fa fa-chevron-up";
            } else {
                body.style.display = "none";
                text.innerText = "Modulfortschritt einblenden";
                icon.className = "fa fa-chevron-down";
            }
        }
        </script>
        ';

        $this->content->text = $html;
        return $this->content;
    }

    private function get_course_percent(int $userid, int $courseid): float {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/grade/lib.php');

        $gradeitem = grade_item::fetch_course_item($courseid);

        if (!$gradeitem || empty($gradeitem->id)) {
            return 0.0;
        }

        $grade = $DB->get_record('grade_grades', [
            'userid' => $userid,
            'itemid' => $gradeitem->id
        ]);

        if (!$grade || $grade->finalgrade === null) {
            return 0.0;
        }

        if (empty($gradeitem->grademax) || $gradeitem->grademax <= 0) {
            return 0.0;
        }

        $percent = ($grade->finalgrade / $gradeitem->grademax) * 100;

        return max(0, min(100, round($percent, 1)));
    }

    private function get_status(float $percent): array {
        if ($percent >= 90) {
            return [
                'title' => 'Gold erreicht!',
                'message' => 'Du bist im Spitzenbereich. Jetzt geht es darum, dein Niveau zu halten.'
            ];
        }

        if ($percent >= 80) {
            return [
                'title' => 'Sehr gut!',
                'message' => 'Du bist im Silber-Bereich. Gold ist realistisch erreichbar.'
            ];
        }

        if ($percent >= 70) {
            return [
                'title' => 'Guter Fortschritt!',
                'message' => 'Du bist im Bronze-Bereich. Der nächste Schritt ist Silber.'
            ];
        }

        if ($percent >= 50) {
            return [
                'title' => 'Aufbauphase',
                'message' => 'Du bist noch unter Bronze. Mit gezieltem Fokus kannst du schnell aufholen.'
            ];
        }

        return [
            'title' => 'Dranbleiben!',
            'message' => 'Starte mit den wichtigsten Leistungsbereichen, um deinen Wert sichtbar zu erhöhen.'
        ];
    }

    private function get_progress_to_next(float $percent): array {
        if ($percent >= 90) {
            return [
                'next' => 'Gold',
                'missing' => 0,
                'progress' => 100
            ];
        }

        if ($percent >= 80) {
            return [
                'next' => 'Gold',
                'missing' => round(90 - $percent, 1),
                'progress' => round((($percent - 80) / 10) * 100)
            ];
        }

        if ($percent >= 70) {
            return [
                'next' => 'Silber',
                'missing' => round(80 - $percent, 1),
                'progress' => round((($percent - 70) / 10) * 100)
            ];
        }

        return [
            'next' => 'Bronze',
            'missing' => round(70 - $percent, 1),
            'progress' => round(($percent / 70) * 100)
        ];
    }

    private function get_recommendation(float $percent): string {
        if ($percent >= 90) {
            return 'Fokus: Gold halten. Bleibe bei Aufgaben, Anwesenheit und LSK konstant. Lernstandskontrollen können weiterhin genutzt werden, um deinen Wert zusätzlich abzusichern oder weiter zu verbessern. Falls organisatorische Herausforderungen auftreten, unterstützen dich Dozierende und Kursbetreuung gerne dabei, frühzeitig passende Lösungen zu finden.';
        }

        if ($percent >= 80) {
            return 'Fokus: Gold erreichen. Der größte Hebel liegt weiterhin bei den Aufgabenbewertungen, da diese den höchsten Anteil an der Gesamtkursbewertung haben. Nutze Wiederholungen der Lernstandskontrollen gezielt, um einzelne Prozentpunkte aufzubauen und den Gold-Bereich zu erreichen. Sollten regelmäßige Teilnahmezeiten schwierig werden, empfiehlt sich eine frühzeitige Abstimmung mit Dozierenden und Kursbetreuung.';
        }

        if ($percent >= 70) {
            return 'Fokus: Silber erreichen. Du hast bereits eine starke Basis aufgebaut. Um Silber zu erreichen, solltest du vor allem Aufgabenbewertungen optimieren und offene Abgaben möglichst zeitnah nacharbeiten. Lernstandskontrollen kannst du beliebig oft wiederholen, um deinen Prozentwert weiter zu verbessern. Achte zusätzlich auf eine stabile Teilnahme. Falls es dabei Herausforderungen gibt, unterstützen dich Dozierende und Kursbetreuung gerne bei einer gemeinsamen Lösung.';
        }

        if ($percent >= 50) {
            return 'Fokus: Bronze sichern. Konzentriere dich zuerst auf offene oder schwächere Aufgabenbewertungen, da Aufgaben mit 40 % den größten Einfluss auf die Gesamtkursbewertung haben. Nachbearbeitungen helfen dir weiterhin Punkte aufzubauen — auch wenn nicht mehr die volle Punktzahl erreichbar ist. Nutze Wiederholungen der Lernstandskontrollen gezielt, um deinen Wert kontinuierlich zu steigern. Falls regelmäßige Teilnahme schwierig ist, suche frühzeitig gemeinsam mit Dozierenden und Kursbetreuung nach einer passenden Lösung.';
        }

        return 'Fokus: Basis aufbauen. Bearbeite zuerst offene Aufgaben, da diese den größten Einfluss auf deine Gesamtkursbewertung haben. Nachbearbeitungen sind möglich, werden jedoch nicht mehr mit der vollen Punktzahl bewertet. Lernstandskontrollen kannst du beliebig oft wiederholen, um deinen Prozentwert Schritt für Schritt zu verbessern. Falls du regelmäßig verhindert bist teilzunehmen, sprich frühzeitig mit deinen Dozierenden und der Kursbetreuung, damit gemeinsam eine Lösung gefunden werden kann.';
    }

    private function get_badge_preview(float $percent): string {
        $badges = [
            ['name' => 'Bronze', 'threshold' => 70],
            ['name' => 'Silber', 'threshold' => 80],
            ['name' => 'Gold', 'threshold' => 90]
        ];

        $html = '';

        foreach ($badges as $badge) {
            $unlocked = $percent >= $badge['threshold'];
            $class = $unlocked ? 'unlocked' : 'locked';
            $symbol = $unlocked ? '✓' : '○';

            $html .= '
                <div class="moduleprogress-badge ' . $class . '">
                    <div class="moduleprogress-badge-icon">' . $symbol . '</div>
                    <div class="moduleprogress-badge-name">' . s($badge['name']) . '</div>
                    <div class="moduleprogress-badge-threshold">ab ' . s($badge['threshold']) . '%</div>
                </div>
            ';
        }

        return $html;
    }

    private function get_ranking_courses(int $currentcourseid): array {
        if (empty($this->config->rankingcourses)) {
            return [$currentcourseid];
        }

        $ids = explode(',', $this->config->rankingcourses);
        $courseids = [];

        foreach ($ids as $id) {
            $id = (int)trim($id);
            if ($id > 0) {
                $courseids[] = $id;
            }
        }

        return empty($courseids) ? [$currentcourseid] : array_values(array_unique($courseids));
    }

    private function get_leaderboard_html(int $currentuserid, array $courseids, int $maxrows, array $student_ids, bool $is_student): string {
        global $DB;

        $userscores = [];

        foreach ($courseids as $courseid) {
            $context = context_course::instance($courseid, IGNORE_MISSING);

            if (!$context) {
                continue;
            }

            // FILTER: Nur Teilnehmende berücksichtigen
            $users = get_enrolled_users($context, '', 0, 'u.id');

            foreach ($users as $user) {
                // Wenn kein Student, überspringen
                if (!empty($student_ids) && !in_array($user->id, $student_ids)) {
                    continue;
                }

                $percent = $this->get_course_percent((int)$user->id, (int)$courseid);

                if (!isset($userscores[$user->id])) {
                    $userscores[$user->id] = [
                        'userid' => (int)$user->id,
                        'total' => 0,
                        'count' => 0
                    ];
                }

                $userscores[$user->id]['total'] += $percent;
                $userscores[$user->id]['count']++;
            }
        }

        foreach ($userscores as $userid => $data) {
            $userscores[$userid]['score'] = round($data['total'] / max(1, $data['count']), 1);
        }

        usort($userscores, function($a, $b) {
            if ($a['score'] == $b['score']) {
                return 0;
            }

            return ($a['score'] < $b['score']) ? 1 : -1;
        });

        $currentrank = null;
        $currentscore = 0;

        foreach ($userscores as $index => $row) {
            if ((int)$row['userid'] === (int)$currentuserid) {
                $currentrank = $index + 1;
                $currentscore = $row['score'];
                break;
            }
        }

        // Falls User kein Student ist (z.B. Dozent)
        if ($currentrank === null) {
            $currentrank = '-';
            $currentscore = 0;
        }

        $total = count($userscores);

        $nextscore = null;

        if (is_numeric($currentrank) && $currentrank > 1 && isset($userscores[$currentrank - 2])) {
            $nextscore = $userscores[$currentrank - 2]['score'];
        }

        $missingtonext = $nextscore !== null
            ? max(0, round($nextscore - $currentscore + 0.1, 1))
            : 0;

        if ($currentrank === 1) {
            $coachtitle = 'Du führst!';
            $coachtext = 'Stark — du bist aktuell ganz vorne. Halte dein Niveau und sichere deinen Vorsprung.';
            $coachgoal = 'Ziel: Platz 1 halten';
        } else if ($missingtonext <= 2 && is_numeric($currentrank)) {
            $coachtitle = 'Greifbar nah!';
            $coachtext = 'Nur wenige Prozentpunkte trennen dich vom nächsten Platz. Wiederhole eine LSK oder optimiere eine Aufgabe.';
            $coachgoal = 'Noch ' . $missingtonext . ' % bis Platz #' . ($currentrank - 1);
        } else if ($currentscore < 70) {
            $coachtitle = 'Nächstes Ziel: Bronze';
            $coachtext = 'Konzentriere dich auf offene Aufgaben und wiederhole Lernstandskontrollen. Jeder Prozentpunkt bringt dich sichtbar nach vorne.';
            $coachgoal = 'Noch ' . round(70 - $currentscore, 1) . ' % bis Bronze';
        } else if ($currentscore < 80) {
            $coachtitle = 'Nächstes Ziel: Silber';
            $coachtext = 'Du hast Bronze erreicht. Mit gezielter Nacharbeit und LSK-Wiederholungen ist Silber realistisch.';
            $coachgoal = 'Noch ' . round(80 - $currentscore, 1) . ' % bis Silber';
        } else if ($currentscore < 90) {
            $coachtitle = 'Nächstes Ziel: Gold';
            $coachtext = 'Gold ist in Reichweite. Nutze LSK-Wiederholungen für die letzten Prozentpunkte.';
            $coachgoal = 'Noch ' . round(90 - $currentscore, 1) . ' % bis Gold';
        } else {
            $coachtitle = 'Gold-Level';
            $coachtext = 'Du bist im Spitzenbereich. Jetzt geht es darum, konstant zu bleiben.';
            $coachgoal = 'Gold halten';
        }

        $visible = array_slice($userscores, 0, max(3, $maxrows));
        $rows = '';

        foreach ($visible as $index => $row) {
            $rank = $index + 1;
            $isme = (int)$row['userid'] === (int)$currentuserid;
            $class = $isme ? ' isme' : '';
            $name = $isme ? 'Du' : 'Teilnehmer ' . $rank;

            $rows .= '
                <div class="moduleprogress-rank-row' . $class . '">
                    <div class="moduleprogress-rank-place">#' . s($rank) . '</div>
                    <div class="moduleprogress-rank-name">' . s($name) . '</div>
                    <div class="moduleprogress-rank-score">' . s($row['score']) . '%</div>
                </div>
            ';
        }

        if (is_numeric($currentrank) && $currentrank > $maxrows) {
            $rows .= '
                <div class="moduleprogress-rank-divider">…</div>
                <div class="moduleprogress-rank-row isme">
                    <div class="moduleprogress-rank-place">#' . s($currentrank) . '</div>
                    <div class="moduleprogress-rank-name">Du</div>
                    <div class="moduleprogress-rank-score">' . s($currentscore) . '%</div>
                </div>
            ';
        }

        return '
            <div class="moduleprogress-leaderboard">
                <div class="moduleprogress-leaderboard-header">
                    <div>
                        <strong>Anonymisiertes Kohorten-Ranking</strong>
                        <span>Deine Position ist sichtbar — andere Teilnehmende bleiben anonym.</span>
                    </div>
                    <div class="moduleprogress-own-rank">#' . s($currentrank) . ' von ' . s($total) . '</div>
                </div>

                <div class="moduleprogress-leaderboard-grid">
                    <div class="moduleprogress-rank-list">
                        ' . $rows . '
                    </div>

                    <div class="moduleprogress-ranking-coach">
                        <div class="moduleprogress-coach-label">Dein nächster Schritt</div>
                        <div class="moduleprogress-coach-title">' . s($coachtitle) . '</div>
                        <div class="moduleprogress-coach-message">' . s($coachtext) . '</div>
                        <div class="moduleprogress-coach-goal">' . s($coachgoal) . '</div>
                    </div>
                </div>
            </div>
        ';
    }
}
