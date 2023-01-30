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

namespace local_musi\output;

use local_musi\output\tabs\overview;
use local_musi\output\tabs\teacher;
use local_musi\output\tabs\sports;
use local_musi\output\tabs\entities;
use renderer_base;
use renderable;
use stdClass;
use templatable;

/**
* This class prepares data for displaying a booking option instance
*
* @package local_musi
* @copyright 2023 Michael Koscher {@link http://www.aau.at}
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class main_tabs {

    /**
     * Create standard dashboard.
     *
     * @return void
     */
    public function get_main_tabs() {

        return [
            $this->overview(),
            $this->teacher(),
            $this->sports(),
            $this->entities()
        ];
    }

    private function overview(){
        return new overview(['menuicon'=>'fa-home']);
    }
    private function teacher(){
        return new teacher(['menuicon'=>'fa-users']);
    }
    private function sports(){
        return new sports(['menuicon'=>'fa-futbol-o']);
    }
    private function entities(){
        return new entities(['menuicon'=>'fa-map-marker']);
    }
}