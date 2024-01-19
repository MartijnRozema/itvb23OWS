pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script { scannerHome = tool 'ows-sonarqube-scanner' }
                withSonarQubeEnv('ows-sonarqube-server') {
                    sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=dev-martijn-ows -Dsonar.login=sqa_698500c4569d060f9207c12442ccb0247e5f3021"
                }
                echo 'Sonarqube working...'
            }
        }
    }

    post {
        success {
            echo 'Pipeline was successful.'
        }
        failure {
            echo 'Failure in pipeline!'
        }
    }
}
