<?php
declare(strict_types=1);

namespace Sirius\Upload\Result;

interface ResultInterface
{
    /**
     * Returns if the uploaded file is valid
     *
     * @return bool
     */
    public function isValid():bool;

    /**
     * Returns the validation error messages
     *
     * @return array
     */
    public function getMessages():array;

    /**
     * The file that was saved during process() and has a .lock file attached
     * will be cleared, in case the form processing fails
     */
    public function clear();

    /**
     * Remove the .lock file attached to the file that was saved during process()
     * This should happen if the form fails validation/processing
     */
    public function confirm();
}
