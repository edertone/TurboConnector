# How to make the library available to the public

1 - Update project dependencies by following instructions on upgrade-dependencies.md

2 - Make sure all tests pass

3 - Commit and push all the new version changes to repository.

4 - Review git changelog to decide the new version value based on the GIT changes: minor, major, ...

5 - Make sure the git tag is updated with the new project version we want to publish
    (First in remote GIT repo and then in our Local by performing a fetch)

6 - Generate a release build (tb -cr)
     - Make sure the phar is generated

7 - For now we are not publishing the library to composer, cause it requires the composer.json file to be on github root
    - so skip composer publishing

8 - Upload the lib to the respective site or server
    TODO