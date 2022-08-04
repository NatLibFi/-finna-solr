# Finna Solr

Solr for Finna (VuFind)

This is the Finna VuFind configuration for Solr. Important bits:

- Solr distribution is installed into vendor directory
- Solr home (set in solr.in.finna.sh[.sample]) is ./vufind which contains the Finna VuFind core configs
- The JTS libraries (from https://sourceforge.net/projects/jts-topo-suite/) are added to vendor/server/solr-webapp/webapp/WEB-INF/lib (putting them in vufind/lib doesn't seem to work, probably because of SOLR-4852 and SOLR-6188, and trying workaround still doesn't let JTS load properly):
  - jts
  - jtsio
- The following libraries are copied to vufind/lib (having them in solrconfig.xml doesn't play nice with dynamic collection management in SolrCloud):
  - vendor/contrib/analysis-extras/lib/icu4j-*.jar
  - vendor/contrib/analysis-extras/lucene-libs/lucene-analyzers-icu-*.jar
- Raudikko (a pure Java implementation of Voikko) is included for Finnish language processing

## Installation

1. Put the files somewhere
2. Add user solr
3. Run ./install_solr.sh
4. chown the files and directories to solr user
5. Copy vufind/solr.in.finna.sh.sample to vufind/solr.in.finna.sh and edit as required
6. Use the following commands to start or stop Solr manually:

        SOLR_INCLUDE=vufind/solr.in.finna.sh vendor/bin/solr start

        SOLR_INCLUDE=vufind/solr.in.finna.sh vendor/bin/solr stop

7. To enable startup via system init and management with service command in init-based systems like RHEL 6.x, copy vufind/solr.finna-init-script to file /etc/init.d/solr, make it executable, change the paths in it and execute the following commands:

        chkconfig --add solr
        chkconfig solr on

8. With systemd-based systemd, like CentOS 7, copy vufind/solr.service to /etc/systemd/system/, change paths in it and execute the following commands:

        systemctl daemon-reload
        systemctl enable solr

9. In init-based systems, start and stop Solr with commands:

        service solr start

        service solr stop

10. In systemd-based systems, start and stop Solr with commands:

        systemctl start solr

        systemctl stop solr

11. Check the logs at vufind/logs for any errors

12. If running in solrcloud mode, use a chroot and make sure to create the root directory in zkCli:

        zookeeper-x.y.z/bin/zkCli.sh -server 127.0.0.1:2181
        create /solr []

    Then you can use the following command to add a core configuration to Zookeeper:

        SOLR_INCLUDE=vufind/solr.in.finna.sh vendor/bin/solr zk upconfig -n biblio3 -d vufind/biblio/conf

    In production when using an external Zookeeper, its address is specified in solr.in.finna.sh. If you're running SolrCloud with the embedded Zookeeper (for development purposes), you'll need to specify Zookeeper address with the -z parameter (Zookeeper port is Solr's port + 1000):

        SOLR_INCLUDE=vufind/solr.in.finna.sh vendor/bin/solr zk upconfig -z localhost:9983 -n biblio3 -d vufind/biblio/conf

    Then you can create a new collection that uses the configuration by calling the collections API:

        curl 'http://localhost:8983/solr/admin/collections?action=CREATE&name=biblio3&numShards=1&replicationFactor=3&collection.configName=biblio3'

    If you need to create a collection on just a single node of a SolrCloud, you can use the placement rules to
    define the location, e.g.

        curl 'http://localhost:8983/solr/admin/collections?action=CREATE&name=biblio3&numShards=1&replicationFactor=1&collection.configName=biblio3&rule=shard:*,host:domain.somewhere'

    If you want to have more than one shard per node, use the maxShardsPerNode parameter:

        curl 'http://localhost:8983/solr/admin/collections?action=CREATE&name=biblio3&numShards=3&replicationFactor=3&collection.configName=biblio3&maxShardsPerNode=3'

    Add a replica to a collection:

        curl 'http://localhost:8983/solr/admin/collections?action=ADDREPLICA&collection=biblio3&shard=shard1&node=domain.somewhere:8983_solr&type=nrt'

    Remove a replica from a collection:

        curl 'http://localhost:8983/solr/admin/collections?action=DELETEREPLICA&collection=biblio3&shard=shard1&replica=core_nodeX'

    Use an alias to point to the current index version in use. This way you can just point the alias to a new index version when it's ready to use:

        curl 'http://localhost:8983/solr/admin/collections?action=CREATEALIAS&name=biblioprod&collections=biblio3'

    If you need to reload a collection e.g. for new configuration to take effect, you can do it using the collections API:

        curl 'http://localhost:8983/solr/admin/collections?action=RELOAD&name=biblio3'

    When a collection is no longer needed, remove it using the collections API:

        curl 'http://localhost:8983/solr/admin/collections?action=DELETE&name=biblio3'

    To check the SolrCloud status:

        scripts/cluster_status.php

    or

        curl 'http://localhost:8983/solr/admin/collections?action=clusterstatus&wt=json' | json_reformat

    Full docs for the collections API: https://lucene.apache.org/solr/guide/7_1/collections-api.html

13. To run a second Solr instance:

        scripts/create_instance_dir vufind8984

        SOLR_HOME=`pwd`/vufind8984 SOLR_PORT=8984 SOLR_INCLUDE=vufind/solr.in.finna.sh vendor/bin/solr start

## Update

1. Pull the changes
2. Run ./installsolr.sh
