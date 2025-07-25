pipeline {
    agent any
    triggers{ cron( getCronParams() ) }

    environment {
        RPM_PUBLIC_KEY  = credentials('ingrid-rpm-public')
        RPM_PRIVATE_KEY = credentials('ingrid-rpm-private')
        RPM_SIGN_PASSPHRASE = credentials('ingrid-rpm-passphrase')
        SYFT_VERSION = 'latest'
    }

    stages {
        stage('Update submodule') {
            when { expression { return shouldBuildDevOrRelease() } }
            steps {
                withCredentials([gitUsernamePassword(credentialsId: 'ae3a7670-c4c8-413c-9df2-45373f1723a2', gitToolName: 'git')]) {
                    sh 'git submodule update --init --remote --recursive'
                }
            }
        }

        stage('Build image') {
            when { expression { return shouldBuildDevOrRelease() && shouldBuildDockerImage() } }
            steps {
                echo 'Starting to build docker image'

                script {
                    // remove build dir containing previous RPM
                    sh 'if [ -d build ]; then rm -rf build; fi'

                    if (env.TAG_NAME) {
                        env.VERSION = env.TAG_NAME
                    } else if (BRANCH_NAME == 'main') {
                        env.VERSION = 'latest'
                    } else {
                        env.VERSION = BRANCH_NAME.replaceAll('/', '-')
                    }

                    docker.withRegistry('https://docker-registry.wemove.com', 'docker-registry-wemove') {
                        def customImage = docker.build("docker-registry.wemove.com/ingrid-portal:${env.VERSION}", "--pull .")

                        /* Push the container to the custom Registry */
                        customImage.push()
                    }
                }
            }
        }

        stage ('Base-Image Update') {
            when {
                allOf {
                    buildingTag()
                    expression { return currentBuild.number > 1 && shouldBuildDockerImage() }
                }
            }
            steps {
                script {
                    docker.withRegistry('https://docker-registry.wemove.com', 'docker-registry-wemove') {
                        def customImage = docker.build("docker-registry.wemove.com/ingrid-portal:${env.TAG_NAME}", "--pull .")

                        /* Push the container to the custom Registry */
                        customImage.push()
                    }
                }
            }
        }

        stage('Build RPM') {
            when { expression { return shouldBuildDevOrRelease() } }
            agent {
                docker {
                    image 'docker-registry.wemove.com/ingrid-rpmbuilder-php8'
                    reuseNode true
                }
            }
            steps {
                echo 'Starting to build RPM package'

                script {
                    sh "sed -i 's/^Version:.*/Version: ${determineVersion()}/' rpm/ingrid-portal.spec"
                    sh "sed -i 's/^Release:.*/Release: ${determineRpmReleasePart()}/' rpm/ingrid-portal.spec"

                    sh """
                        cp ${WORKSPACE}/rpm/ingrid-portal.spec /root/rpmbuild/SPECS/ingrid-portal.spec &&
                        rpmbuild -bb /root/rpmbuild/SPECS/ingrid-portal.spec &&
                        gpg --batch --import $RPM_PUBLIC_KEY &&
                        gpg --batch --import $RPM_PRIVATE_KEY &&
                        expect /rpm-sign.exp /root/rpmbuild/RPMS/noarch/*.rpm
                    """

                    sh "mkdir -p ./build"
                    sh "cp -r /root/rpmbuild/RPMS/noarch/* ${WORKSPACE}/build/"

                    archiveArtifacts artifacts: 'build/ingrid-portal-*.rpm', fingerprint: true
                }
            }
        }

        stage('Build SBOM') {
            when { expression { return shouldBuildDevOrRelease() } }
            steps {
                echo 'Generating Software Bill of Materials (SBOM)'

                script {
                    def imageToScan = "docker-registry.wemove.com/ingrid-portal:${env.VERSION}"
                    def sbomFilename = "ingrid-portal-${determineVersion()}-sbom.json"

                    sh """
                        docker run --rm --pull=always --volumes-from jenkins anchore/syft:latest ${imageToScan} --output cyclonedx-json=${WORKSPACE}/build/${sbomFilename}
                    """
                    // Archive the SBOM file as an artifact
                    archiveArtifacts artifacts: "build/${sbomFilename}", fingerprint: true
                }
            }
        }

        stage('Deploy RPM & SBOM') {
            when { expression { return shouldBuildDevOrRelease() } }
            steps {
                script {
                    def repoType = env.TAG_NAME ? "rpm-ingrid-releases" : "rpm-ingrid-snapshots"
                    withCredentials([usernamePassword(credentialsId: '9623a365-d592-47eb-9029-a2de40453f68', passwordVariable: 'PASSWORD', usernameVariable: 'USERNAME')]) {
                        sh '''
                            curl -f --user $USERNAME:$PASSWORD --upload-file build/*.rpm https://nexus.informationgrid.eu/repository/''' + repoType + '''/
                            curl -f --user $USERNAME:$PASSWORD --upload-file build/*-sbom.json https://nexus.informationgrid.eu/repository/''' + repoType + '''/
                        '''
                    }
                }
            }
        }
    }
}

def getCronParams() {
    String tagTimestamp = env.TAG_TIMESTAMP
    long diffInDays = 0
    if (tagTimestamp != null) {
        long diff = "${currentBuild.startTimeInMillis}".toLong() - "${tagTimestamp}".toLong()
        diffInDays = diff / (1000 * 60 * 60 * 24)
        echo "Days since release: ${diffInDays}"
    }

    def versionMatcher = /\d\.\d\.\d(.\d)?/
    if( env.TAG_NAME ==~ versionMatcher && diffInDays < 180) {
        // every Sunday between midnight and 6am
        return 'H H(0-6) * * 0'
    }
    else {
        return ''
    }
}

def determineVersion() {
    if (env.TAG_NAME) {
        if (env.TAG_NAME.startsWith("RPM-")) { // e.g. RPM-8.0.0-0.1SNAPSHOT
            def lastDashIndex = env.TAG_NAME.lastIndexOf("-")
            return env.TAG_NAME.substring(4, lastDashIndex)
        }
        return env.TAG_NAME
    } else {
        return env.BRANCH_NAME.replaceAll('/', '_')
    }
}

def determineRpmReleasePart() {
    if (env.TAG_NAME) {
        if (env.TAG_NAME.startsWith("RPM-")) {
            return env.TAG_NAME.substring(env.TAG_NAME.lastIndexOf("-") + 1)
        }
        return '1'
    } else {
        return 'dev'
    }
}

def shouldBuildDevOrRelease() {
    // If no tag is being built OR it is the first build of a tag
    boolean isTag = env.TAG_NAME != null && env.TAG_NAME.trim() != ''
    return !isTag || (isTag && currentBuild.number == 1)
}

def shouldBuildDockerImage() {
    if (env.TAG_NAME && env.TAG_NAME.startsWith("RPM-")) {
        return false
    } else return true
}
