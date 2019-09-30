<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    Exceptions.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Facades\ImageJobs;


/**
 * @author Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */
interface Exception
{

}



/**
 * @author Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */
class InvalidStateException extends \RuntimeException implements Exception
{

}


/**
 * @author Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */
class NotSupportedException extends \LogicException implements Exception
{

}


/**
 * @author Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}

