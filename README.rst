Google Cloud Storage
====================

.. contents::
    :local:

Description
-----------

.. |link1| replace:: Google Cloud Storage
.. |link2| replace:: Google Cloud CDN
.. |link3| replace:: Documentation
.. |link4| replace:: Creating and Managing Service Account Keys
.. |link5| replace:: env.php.sample
.. _link1: https://cloud.google.com/storage/
.. _link2: https://cloud.google.com/cdn/
.. _link3: https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/latest/index.html
.. _link4: https://cloud.google.com/iam/docs/creating-managing-service-account-keys
.. _link5: https://github.com/auroraextensions/googlecloudstorage/blob/master/env.php.sample

Use |link1|_ to store media assets in Magento.

Installation
------------

We highly recommend installing via Composer for package management.

.. code-block:: sh

    composer require auroraextensions/googlecloudstorage

Configuration
-------------

Once installed, update the environment configuration file. See |link5|_ for an example.
The following information should be readily available:

1. Google Cloud project ID
2. Path to the Google Cloud service account JSON key file. See `Service Account`_ for more details.
3. Google Cloud Storage bucket name
4. Google Cloud Storage bucket region (if applicable)

Next, enable the module with the Magento autoloader.

.. code-block:: sh

    php bin/magento module:enable AuroraExtensions_GoogleCloudStorage

Synchronization
---------------

You can initiate the bulk synchronization process through the Magento backend, just as you would with
any other media storage configuration. Additionally, you can initiate the bulk synchronization process
from the command line using the provided synchronization CLI command.

.. code-block:: sh

    php bin/magento gcs:media:sync

**IMPORTANT**: This process can be very slow, especially if you have a lot of media files.

Service Account
---------------

For the purposes of authenticating with Google Cloud Platform, this module leverages the flexibility and ease of use provided by Google Cloud service accounts.
Before moving forward, please make sure to complete the following:

1. Create a Google Cloud service account with **Storage Admin** privileges. Once the service account is created, you will be prompted to download a JSON key file. Store this key file in a safe place.
2. Install the service account JSON key file to the local or mounted filesystem with read-only permissions for the Magento user.
3. Verify the following fields are set and correct in the environment configuration file (env.php):
    1. All required fields
    2. The Google Cloud project name is where the bucket exists
    3. The path to the Google Cloud service account JSON key file (e.g. /etc/gcs.json). Relative paths are assumed to be relative to the Magento root directory.
    4. [OPTIONAL] If you use the same bucket for multiple projects, you can specify a subdirectory to synchronize to inside the bucket. By default, it will synchronize to /.

For more information on Google Cloud service account keys, please see |link4|_.

For an example configuration file, please see |link5|_.

Troubleshooting
---------------

    Given keyfile at path /path/to/magento was invalid

You need to create and install a service account key to authenticate with Google Cloud. See `Service Account`_ for specific details on Google Cloud service accounts.
