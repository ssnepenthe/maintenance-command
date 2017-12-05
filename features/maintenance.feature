Feature: Manage maintenance mode

  Scenario: Maintenance mode is disabled by default
    Given a WP install

    When I run `wp maintenance status`
    Then STDOUT should be:
      """
      Maintenance mode is currently disabled
      """

  Scenario: Maintenance mode can be enabled
    Given a WP install
    And I run `wp eval 'echo ABSPATH;'`
    And save STDOUT as {ABSPATH}

    When I run `wp maintenance status`
    Then the {ABSPATH}/.maintenance file should not exist
    And STDOUT should be:
      """
      Maintenance mode is currently disabled
      """

    When I run `wp maintenance enable`
    Then the {ABSPATH}/.maintenance file should exist
    And STDOUT should be:
      """
      Success: Maintenance mode enabled
      """

    When I run `wp maintenance status`
    Then STDOUT should be:
      """
      Maintenance mode is currently enabled
      """

  Scenario: Maintenance mode can be disabled
    Given a WP install
    And I run `wp eval 'echo ABSPATH;'`
    And save STDOUT as {ABSPATH}
    And I run `echo "<?php \$upgrading = $(date +%s); ?>" > .maintenance`

    When I run `wp maintenance status`
    Then the {ABSPATH}/.maintenance file should exist
    And STDOUT should be:
      """
      Maintenance mode is currently enabled
      """

    When I run `wp maintenance disable`
    Then the {ABSPATH}/.maintenance file should not exist
    And STDOUT should be:
      """
      Success: Maintenance mode disabled
      """

    When I run `wp maintenance status`
    Then STDOUT should be:
      """
      Maintenance mode is currently disabled
      """

  Scenario: Maintenance mode can be toggled
    Given a WP install

    When I run `wp maintenance status`
    Then STDOUT should be:
      """
      Maintenance mode is currently disabled
      """

    When I run `wp maintenance toggle`
    Then STDOUT should be:
      """
      Success: Maintenance mode enabled
      """

    When I run `wp maintenance status`
    Then STDOUT should be:
      """
      Maintenance mode is currently enabled
      """

    When I run `wp maintenance toggle`
    Then STDOUT should be:
      """
      Success: Maintenance mode disabled
      """

    When I run `wp maintenance status`
    Then STDOUT should be:
      """
      Maintenance mode is currently disabled
      """

  Scenario: Maintenance mode stays enabled for 10 minutes
    Given a WP install
    And I run `wp eval 'echo ABSPATH;'`
    And save STDOUT as {ABSPATH}
    And I run `echo "<?php \$upgrading = $(date --date='-595sec' +%s); ?>" > .maintenance`

    When I run `wp maintenance status`
    Then the {ABSPATH}/.maintenance file should exist
    And STDOUT should be:
      """
      Maintenance mode is currently enabled
      """

  Scenario: Maintenance mode gets disabled after 10 minutes
    Given a WP install
    And I run `wp eval 'echo ABSPATH;'`
    And save STDOUT as {ABSPATH}
    And I run `echo "<?php \$upgrading = $(date --date='-605sec' +%s); ?>" > .maintenance`

    When I run `wp maintenance status`
    Then the {ABSPATH}/.maintenance file should exist
    But STDOUT should be:
      """
      Maintenance mode is currently disabled
      """
