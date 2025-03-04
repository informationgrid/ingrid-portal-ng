pipeline {
    agent any
    stages {
        stage('Build image') {
            steps {
                echo 'Starting to build docker image'

                script {

                    if (BRANCH_NAME == 'main') {
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
    }
}
