pipeline {
    agent any
    stages {
        stage('Update submodule') {
            steps {
                withCredentials([gitUsernamePassword(credentialsId: 'ae3a7670-c4c8-413c-9df2-45373f1723a2', gitToolName: 'git')]) {
                    sh 'git submodule update --init --remote --recursive'
                }
            }
        }
        stage('Build image') {
            steps {
                echo 'Starting to build docker image'

                script {

                    if (BRANCH_NAME == 'develop') {
                        env.VERSION = 'latest'
                    } else {
                        env.VERSION = BRANCH_NAME.replaceAll('/', '-')
                    }

                    docker.withRegistry('https://docker-registry.wemove.com', 'docker-registry-wemove') {
                        def customImage = docker.build("docker-registry.wemove.com/ingrid-portal-ng:${env.VERSION}", "--pull .")

                        /* Push the container to the custom Registry */
                        customImage.push()
                    }
                }
            }
        }
        stage('Build RPM') {
            steps {
                echo 'Starting to build RPM package'

                script {
                    // Set RPM version based on git information
                    def gitCommitShort = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                    def gitTimestamp = sh(script: 'git log -1 --format=%cd --date=format:%Y%m%d%H%M%S', returnStdout: true).trim()

                    // Use the same version logic as for Docker image but add git information for RPM
                    if (BRANCH_NAME == 'develop') {
                        env.RPM_VERSION = "1.0.0"
                        env.RPM_RELEASE = "dev.${gitTimestamp}.${gitCommitShort}"
                    } else if (BRANCH_NAME ==~ /^release\/.*/) {
                        // Extract version from release branch (e.g., release/1.2.3 -> 1.2.3)
                        env.RPM_VERSION = BRANCH_NAME.replaceAll('release/', '')
                        env.RPM_RELEASE = "1"
                    } else {
                        env.RPM_VERSION = "1.0.0"
                        env.RPM_RELEASE = "${BRANCH_NAME.replaceAll('/', '-')}.${gitTimestamp}.${gitCommitShort}"
                    }

                    // Process the spec file template
                    def specTemplate = readFile 'ingrid-portal-ng.spec.template'
                    def buildDate = sh(script: 'date "+%a %b %d %Y"', returnStdout: true).trim()

                    // Replace placeholders with actual values
//                    def processedSpec = specTemplate
//                        .replaceAll('@RPM_VERSION@', env.RPM_VERSION)
//                        .replaceAll('@RPM_RELEASE@', env.RPM_RELEASE)
//                        .replaceAll('@BUILD_DATE@', buildDate)
//
//                    // Write the processed spec file
//                    writeFile file: 'ingrid-portal-ng.spec', text: processedSpec

                    // Build RPM using rpmbuild in a Docker container
                    // Verwenden Sie docker cp für die Dateiübertragung anstelle der direkten Volume-Zuweisung.
                    // Dies vermeidet Probleme mit Pfaden in Docker-in-Docker-Setups.
                    def containerId = sh(script: "docker create --entrypoint=\"\" docker-registry.wemove.com/ingrid-rpmbuilder-php8 tail -f /dev/null", returnStdout: true).trim()

                    try {
                        // Kopieren Sie das gesamte Projektverzeichnis in das /src-Verzeichnis des Containers.
                        // Der '.' am Ende des Quellpfads stellt sicher, dass der Inhalt des aktuellen Verzeichnisses kopiert wird.
                        sh "docker cp user ${containerId}:/src_user"
                        // Kopieren Sie die generierte Spec-Datei an den erwarteten Ort im Container.
                        sh "docker cp ingrid-portal-ng.spec ${containerId}:/root/rpmbuild/SPECS/ingrid-portal-ng.spec"

                        // Starten Sie den Container
                        sh "docker start ${containerId}"

                        // Starten Sie den Container und führen Sie rpmbuild aus.
                        sh """
                            docker exec ${containerId} bash -c "rpmbuild -bb /root/rpmbuild/SPECS/ingrid-portal-ng.spec"
                        """
                        // Kopieren Sie die gebauten RPMs aus dem Container zurück in den aktuellen Arbeitsbereich.
                        sh "docker cp ${containerId}:/root/rpmbuild/RPMS/noarch/ingrid-portal-ng-0.1.0-dev.noarch.rpm ."

                    } finally {
                        // Bereinigen Sie den Container, auch wenn der Build fehlschlägt.
                        sh "docker rm -f ${containerId}"
                    }

                    // Archive the RPM
                    archiveArtifacts artifacts: 'ingrid-portal-ng-*.rpm', fingerprint: true

                    // Clean up
                    // sh 'rm -f ingrid-portal-ng.spec'
                }
            }
        }

        stage('Deploy RPM') {
            steps {
                withCredentials([usernamePassword(credentialsId: '9623a365-d592-47eb-9029-a2de40453f68', passwordVariable: 'PASSWORD', usernameVariable: 'USERNAME')]) {
                    sh 'curl --user $USERNAME:$PASSWORD --upload-file ./*.rpm https://nexus.informationgrid.eu/repository/rpm-ingrid/'
                }
            }
        }
    }
}
