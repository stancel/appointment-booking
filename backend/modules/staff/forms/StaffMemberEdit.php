<?php
namespace Bookly\Backend\Modules\Staff\Forms;

use Bookly\Lib;

/**
 * Class StaffMemberEdit
 * @package Bookly\Backend\Modules\Staff\Forms
 */
class StaffMemberEdit extends StaffMember
{
    public function configure()
    {
        $this->setFields( array(
            'wp_user_id',
            'full_name',
            'email',
            'phone',
            'attachment_id',
            'google_data',
            'position',
            'info',
            'visibility',
        ) );
    }
}
