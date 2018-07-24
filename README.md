# Stable PDO database class

*Use this class to create a quick, save and stable database connection to mutate.*

Set-up the class. This has to be done only once in the project.

    run::Credentials("{DATABASE}","{USERNAME}","{PASSWORD}","{HOST (127.0.0.1)}","{PORT (3306)}");

To run a query after setting the credentials.

    run::Query("{INSERT QUERY}");

The function will return the data, if data is returned.

    $data = run::Query("{SELECT QUERY}");
