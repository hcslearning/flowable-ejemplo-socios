package cl.ejemplos.flowable.ejemplo_external_worker;

import org.flowable.external.worker.WorkerResult;
import org.flowable.external.worker.WorkerResultBuilder;
import org.springframework.stereotype.Component;

import org.flowable.external.client.AcquiredExternalWorkerJob;
import org.flowable.external.worker.annotation.FlowableWorker;

@Component
public class MyExternalWorker {

    @FlowableWorker(topic = "registrarSocio")
    public WorkerResult processJob(AcquiredExternalWorkerJob job, WorkerResultBuilder resultBuilder) {
        System.out.println("Ejecutando External Worker");
        var variables = job.getVariables();
        var rut    = variables.get("rut");
        var nombre = variables.get("nombre");

        try {
            var socioId = guardarSocioApiRest(rut, nombre);

            return resultBuilder.success()
                    .variable("socioId", socioId)
                    .variable("registrado", true);

        } catch (Exception e) {
            return resultBuilder.failure()
                    .message("Error al registrar socio")
                    .details(e.getMessage());
        }
    }

    private int guardarSocioApiRest(Object rut, Object nombre) {
        // aca debería llamar a su API REST o SOAP
        System.out.println("Guardando Socio vía API REST");
        System.out.println("Rut: " + rut);
        System.out.println("Nombre: " + nombre);
        return 1234;
    }

}