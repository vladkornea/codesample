<?php

require_once 'BaseModel.php';

interface DbUpgradeModelInterface extends BaseModelInterface {
} // DbUpgradeModelInterface

class DbUpgradeModel extends BaseModel implements DbUpgradeModelInterface {
	use DbUpgradeModelTraits;
} // DbUpgradeModel

