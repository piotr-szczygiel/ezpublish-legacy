<?php

class ezpDatabaseTestSuite extends ezpTestSuite
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sets up a test database
     *
     * @return void
     */
    protected function setUp()
    {
        if ( !ezpTestRunner::$consoleInput->getOption( 'db-per-test' )->value )
        {
            $dsnOption = ezpTestRunner::$consoleInput->getOption( 'dsn' );

            if ( $dsnOption->value ) 
            {
                $dsn = new ezpDsn( $dsnOption->value );
            }
            else
            {
                throw new ezcConsoleOptionMandatoryViolationException( $dsnOption );
            }

            $this->sharedFixture = ezpTestDatabaseHelper::create( $dsn );
        }
    }
}
?>