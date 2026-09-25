variable "IMAGE_TAG" {
default = "homolog"
}

variable "SHA_SHORT" {
default = "latest"
}

variable "VITE_REVERB_APP_KEY" {
default = ""
}

variable "VITE_REVERB_HOST" {
default = ""
}

variable "VITE_REVERB_PORT" {
default = ""
}

variable "VITE_REVERB_SCHEME" {
default = ""
}

group "default" {
targets = ["php", "nginx"]
}

target "base" {
context = "."
dockerfile = "docker/production/Dockerfile"

args = {
VITE_REVERB_APP_KEY = VITE_REVERB_APP_KEY
VITE_REVERB_HOST = VITE_REVERB_HOST
VITE_REVERB_PORT = VITE_REVERB_PORT
VITE_REVERB_SCHEME = VITE_REVERB_SCHEME
}
}

target "php" {
inherits = ["base"]

target = "php-prod"

tags = [
"secultceara/efomento:homolog",
]
}

target "nginx" {
inherits = ["base"]

target = "nginx"

tags = [
"secultceara/efomento-nginx:homolog",
]
}
