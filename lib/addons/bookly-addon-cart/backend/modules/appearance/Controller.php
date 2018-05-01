<?php
namespace BooklyCart\Backend\Modules\Appearance;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Appearance\Lib\Helper;

/**
 * Class Controller
 * @package BooklyCart\Backend\Modules\Appearance
 */
class Controller extends BooklyLib\Base\Controller
{
    /**
     * Render Cart tab on Appearance.
     *
     * @param string $progress_tracker
     * @throws
     */
    public function renderAppearance( $progress_tracker )
    {
        $editable = new Helper();

        $this->render( 'appearance', compact( 'progress_tracker', 'editable' ) );
    }
}