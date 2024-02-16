pipeline {
    agent any
    stages {
        stage('Build image') {
            steps {
                echo 'Starting to build docker image'

                script {
                    docker.withRegistry('https://docker-registry.wemove.com', 'docker-registry-wemove') {
                        def customImage = docker.build("docker-registry.wemove.com/ingrid-ige-ng:latest")
                
                        /* Push the container to the custom Registry */
                        customImage.push()
                    }
                }
            }
        }
    }
}