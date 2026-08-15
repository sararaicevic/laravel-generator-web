# MyDSL Generator Docker Image (Example)

This Laravel app expects a Docker image that can be executed like:

`<command> --in /work/input/model.mydsl --out /work/out`

The job mounts a host folder to `/work` and will read/write inside that mount.

## Example build

1. Put your fat-jar next to `Dockerfile.example` as `mydsl-gen.jar`.
2. Build:
   - `docker build -f docker/mydsl-generator/Dockerfile.example -t mydsl-generator:latest docker/mydsl-generator`
3. Configure `.env`:
   - `GENERATOR_DOCKER_IMAGE=mydsl-generator:latest`
   - `GENERATOR_DOCKER_COMMAND="java -jar /app/mydsl-gen.jar"`

